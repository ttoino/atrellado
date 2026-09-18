# AGENTS.md

## Project Overview

A Laravel 13 app deployed to Cloudflare Workers via [workers-php](https://github.com/ttoino/workers-php) (PHP 8.5 compiled to wasm). Data lives in Cloudflare D1 through a custom Laravel database driver, uploaded files in R2, cache in KV, and mail goes out through a Send Email binding.

**Type**: Laravel 13 + PHP 8.5 (wasm)
**Runtime**: Cloudflare Workers
**Package Manager**: pnpm 12 (JS) + Composer 2 (PHP)

## Commands

- `npm run build`: Full build (`build/build.sh` — Composer install via Docker, Vite assets, baked config/route/view caches, app tarball)
- `npm run dev`: Build + `wrangler dev` on port 8799
- `npm run deploy`: Build + `wrangler deploy`
- `npm run migrate:local`: Run migrations against local D1 (via `build/migrate.sh`)
- `npm run migrate:remote`: Run migrations against production D1
- `php artisan`: Usual Laravel CLI (needs local PHP + a local DB for DB-touching commands)
- `php artisan test`: PHPUnit/Pest suite
- `./vendor/bin/pint`: Code style fixer

## Architecture Notes

- **Entry**: `worker.ts` is the Worker entry; `wrangler.jsonc` configures bindings. `staticRoutes` serves `/storage/*` from the R2 `FILES` bucket with `stripPrefix` so keys stay disk-relative.
- **D1 driver**: `app/Providers/D1ServiceProvider.php` registers a custom `d1` connection that executes queries over the `DB` binding instead of a socket. The connection name is injected into the config at registration time — required because the config is cached (see below).
- **Migrations**: no shell access in production, so migrations run in-worker: `POST /_workers/migrate` (key-gated via `config('app.workers_migrate_key')`, CSRF-exempt) calls `Artisan::call('migrate', ['--force' => true])`. `build/migrate.sh` is the curl wrapper.
- **Cached config is baked into the build** (`/persist/app`), so `.env` is NOT loaded at runtime — always read settings via `config()`, never `env()`, outside of config files.
- **R2 storage**: `app/Support/WorkersR2Adapter.php` (Flysystem v3) is registered as the `r2` disk (default via `FILESYSTEM_DRIVER`). `url()` returns `/storage/...` paths that `staticRoutes` serves.
- **KV cache**: `app/Support/WorkersKvStore.php` (registered as the `kv` driver, default via `CACHE_DRIVER`) so rate limits hold across isolates.
- **Mail**: `app/Support/Mailer/WorkersEmailTransport.php` sends through the `EMAIL` Send Email binding (sender `noreply@atrellado.toino.pt`, domain onboarded in Cloudflare Email Routing).
- **Uploads**: the PHP bridge stages multipart bodies as real temp files, but `is_uploaded_file()` fails on them — `AppServiceProvider::boot()` re-marks request uploads as test-mode `UploadedFile` instances so validation/conversion works.
- **Sessions**: cookie driver (no shared state needed).

### Runtime Bindings

| Binding  | Service         | Resource                                          |
| -------- | --------------- | ------------------------------------------------- |
| `DB`     | D1              | `atrellado-db`                                    |
| `FILES`  | R2              | `atrellado-files`                                 |
| `CACHE`  | KV              | `atrellado-cache`                                 |
| `EMAIL`  | Send Email      | `noreply@atrellado.toino.pt`                      |
| `ASSETS` | Static assets   | `./dist` (includes the app tarball)               |
| `APP_ENV`| Environment var | `production`                                      |

### Memory

PHP runs in a fixed 128 MiB wasm memory arena that cannot shrink. A Laravel boot plus one heavy request touches ~110–125 MB, so a fresh isolate serves roughly one heavy request before hitting the wall; subsequent requests on a warmed isolate are fine. Mitigations: config/route/view caches baked into `/persist/app` at build time, cookie-based session/cache drivers, and avoiding per-request `Artisan::call`. Measure locally by watching the `workerd` process RSS during `wrangler dev`.

## Code Style

- **PHP**: Laravel Pint, default preset.
- **Commits**: lowercase imperative summaries (e.g. `Store uploaded files in an R2 bucket`).
- **Comments**: rationale only, never narrative; at most three lines.

## Important Files

- `worker.ts`: Worker entry — bindings, `staticRoutes`, env mapping
- `wrangler.jsonc`: Worker config (D1, R2, KV, Send Email, observability)
- `build/build.sh`: Full production build pipeline
- `build/migrate.sh`: Curl wrapper for the migrate endpoint
- `app/Providers/D1ServiceProvider.php`: Custom D1 database driver
- `app/Providers/AppServiceProvider.php`: `Mail`/`Storage`/`Cache` extensions + upload re-marking
- `app/Support/WorkersR2Adapter.php`: Flysystem adapter over the R2 binding
- `app/Support/WorkersKvStore.php`: Cache store over the KV binding
- `app/Support/Mailer/WorkersEmailTransport.php`: Symfony mailer transport over Send Email
- `app/Http/Controllers/WorkersMigrateController.php`: In-worker migration endpoint
- `config/app.php`, `config/filesystems.php`, `config/mail.php`, `config/cache.php`: Driver registrations and defaults

## Deployment

Cloudflare Workers via Wrangler: `npm run deploy` builds and deploys. Live at [atrellado.toino.pt](https://atrellado.toino.pt/) (also `atrellado.toino.workers.dev`). Secrets (e.g. `ADMIN_PASSWORD`) are managed with `wrangler secret put`. Observability (logs + traces at 100% sampling) is enabled in `wrangler.jsonc`.
