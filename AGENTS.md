# AGENTS.md

## Project Overview

A Laravel 13 project-management app (kanban boards, task groups, tags, threads, notifications) backed by PostgreSQL. The original database triggers are implemented as Eloquent observers (`app/Observers/`).

**Type**: Laravel 13 + PHP 8.5
**Package Manager**: pnpm 12 (JS) + Composer 2 (PHP)

## Commands

- `composer install`: PHP dependencies (no host PHP — run artisan/pest/pint/phpstan through the `Dockerfile.dev` image)
- `pnpm install && pnpm run build`: Frontend assets (Vite)
- `pnpm run check` / `lint` / `format` (+`:fix`): tsc, eslint, prettier
- `php artisan migrate`: Run migrations
- `./vendor/bin/pest`: Pest suite (sqlite in-memory)
- `./vendor/bin/pest tests/Browser`: Browser tests (Pest 4 + playwright chromium against an in-process server; needs `pnpm run build` first; not in `php artisan test`'s suites). LiveUpdateTest additionally needs a reverb server plus `BROADCAST_CONNECTION=reverb REVERB_APP_ID/KEY/SECRET REVERB_HOST=127.0.0.1 REVERB_PORT=8080 REVERB_SCHEME=http` in the env (CI's `browser` job shows the full recipe; without it just that test fails). Forms that post `multipart/form-data` (profile picture) can't be browser-tested — the in-process server only parses urlencoded bodies.
- `./vendor/bin/phpstan analyse --memory-limit=1G`: Larastan, level 5
- `./vendor/bin/pint`: Code style fixer

## Architecture Notes

- **Observers**: `app/Observers/` holds the Eloquent observers that replace the original plpgsql triggers; `creating` observers lock the parent row to serialize `max(position)+1` appends.
- **Auth**: Fortify with `ignoreRoutes()` — routes keep the legacy URL/name contract in `routes/web.php`; OAuth identities match on `provider_user_id` with encrypted token columns.
- **Notifications**: framework `database` channel with denormalized payloads (`notifications.data`); `TaskAssigned` fires on assignment; rows older than 90 days pruned daily via the scheduler.
- **Queue**: `database` driver (`jobs` table); all notifications and `Send*` listeners are queued.
- **Broadcasting**: Laravel Reverb (queued via the database connection) with `private-project.{id}` channels authorized by `ProjectPolicy::view`; the TS pages subscribe via `resources/assets/ts/echo.ts` and re-fetch over the API on `thread.created`/`thread-comment.created`/`task-comment.created` events. The browser config is injected per-request by `layouts/bare.blade.php` from the cached runtime config (never the secret), so the production image needs no build args; dev falls back to `VITE_REVERB_*` from `.env`. In the compose stack the reverse proxy routes the public wss endpoint to the `reverb` service; in the Workers deploy `worker.ts` routes `/app/*` (websocket upgrades) and `POST /apps/*` (app publishes, which also loop through the worker per the Reverb docs) to the `atrellado-reverb` container, a `workers-php` `Container` subclass running `php artisan reverb:start` off the same image.
- **API resources**: `app/Http/Resources/` serializes the comment/thread models for JSON, adding the request-dependent `editable` flag (the models themselves carry no auth-dependent appends).
- **Uploads**: profile pictures are bounded at 4000×4000 px by the form request and processed through the `Image` facade (orient → cover 512×512 → webp) in `UserController`.
- **Observability**: Pulse (admin-gated) in production; Telescope is local-only (`APP_ENV=local` via `AppServiceProvider`).
- **Livewire migration** (branch `livewire`): interactive pages are being ported one-by-one from the hand-rolled TS stack to class-based Livewire 4 components in `app/Livewire/` (views in `resources/views/livewire/`; class-based so phpstan sees them). Full-page components render through the default `layouts::app` layout — `layouts/app.blade.php` carries a `{{ $slot ?? '' }}` hybrid so it serves both `@extends` pages and Livewire. As each page migrates, its TS module, `/api` endpoints, controller methods and blade partials die with it. Pagination stays on plain cursor links (full reloads); Livewire ships only a Tailwind pagination theme.

## Code Style

- **PHP**: Laravel Pint, default preset.
- **Commits**: lowercase imperative summaries.
- **Comments**: rationale only, never narrative; at most three lines.

## Important Files

- `app/Observers/`: Eloquent observers (business triggers)
- `app/Livewire/`, `resources/views/livewire/`: Livewire components (notifications page migrated; rest pending)
- `app/Http/Resources/`: JSON serialization (incl. `editable` flag)
- `database/migrations/`: Schema (driver-agnostic Laravel migrations)
- `Dockerfile`: Production image (single FrankenPHP artifact, one process per container; `etc/entrypoint.sh` caches config from runtime env then drops to www-data; the service with `RUN_MIGRATIONS=true` migrates on boot)
- `Dockerfile.containers`: Cloudflare variant of the image, built by wrangler (the `workers-php` entrypoint migrates then caches config from the env the worker injects; app and reverb containers share it, reverb overrides the entrypoint)
- `compose.production.yaml`: Production stack — four role services off the one image: `web` (Octane :8000, runs migrations, healthcheck on `/up`), `reverb` (:8080, `nofile` ulimit per the Reverb docs), `queue`, `schedule`
- `worker.ts`, `wrangler.jsonc`: Cloudflare deployment on Workers Containers via `workers-php` (npm); D1/R2/KV/queue/email endpoints share one intercepted host (`http://example.com/<BINDING>`); the PHP runtime comes from Packagist (`workers-php/workers-php`); cache rides KV, queued jobs produce to and consume from Cloudflare Queues (internal port 8081); `schedule:run` runs from Cron Triggers; secrets live as worker secrets (`APP_KEY`, `REVERB_APP_SECRET`), declared by name in `wrangler.jsonc` `secrets.required` (deploy validation + typegen source; values are set via `wrangler secret put`, never in the file)
- Previews: the `previews` block in `wrangler.jsonc` redeclares every binding against the shared `atrellado-previews` resources (D1/KV/R2/queue) — previews inherit nothing from production. Preview containers override cache to local disk and queue to sync (Cron Triggers and queue consumers target production only), and `WorkersRequestContext` derives `app.url` and the reverb client config from the request host. Preview secrets come from the base config, set once via `wrangler preview base-config secret put`
- `Dockerfile.dev`, `docker-compose.yaml`: Dev setup (app + postgres 17 + queue worker + reverb)
- `phpstan.neon`, `.github/workflows/`: Larastan config and CI (PHP format/lint/test, JS format/lint/typecheck/build)
