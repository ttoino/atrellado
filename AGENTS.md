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
- `./vendor/bin/phpstan analyse --memory-limit=1G`: Larastan, level 5
- `./vendor/bin/pint`: Code style fixer

## Architecture Notes

- **Observers**: `app/Observers/` holds the Eloquent observers that replace the original plpgsql triggers; `creating` observers lock the parent row to serialize `max(position)+1` appends.
- **Auth**: Fortify with `ignoreRoutes()` — routes keep the legacy URL/name contract in `routes/web.php`; OAuth identities match on `provider_user_id` with encrypted token columns.
- **Notifications**: framework `database` channel with denormalized payloads (`notifications.data`); `TaskAssigned` fires on assignment; rows older than 90 days pruned daily via the scheduler.
- **Queue**: `database` driver (`jobs` table); all notifications and `Send*` listeners are queued.
- **Broadcasting**: Laravel Reverb (queued via the database connection) with `private-project.{id}` channels authorized by `ProjectPolicy::view`; the TS pages subscribe via `resources/assets/ts/echo.ts` and re-fetch over the API on `thread.created`/`thread-comment.created`/`task-comment.created` events. The browser config is injected per-request by `layouts/bare.blade.php` from the cached runtime config (never the secret), so the production image needs no build args; dev falls back to `VITE_REVERB_*` from `.env`.
- **API resources**: `app/Http/Resources/` serializes the comment/thread models for JSON, adding the request-dependent `editable` flag (the models themselves carry no auth-dependent appends).
- **Uploads**: profile pictures are bounded at 4000×4000 px by the form request and processed through the `Image` facade (orient → cover 512×512 → webp) in `UserController`.
- **Observability**: Pulse (admin-gated) in production; Telescope is local-only (`APP_ENV=local` via `AppServiceProvider`).

## Code Style

- **PHP**: Laravel Pint, default preset.
- **Commits**: lowercase imperative summaries.
- **Comments**: rationale only, never narrative; at most three lines.

## Important Files

- `app/Observers/`: Eloquent observers (business triggers)
- `app/Http/Resources/`: JSON serialization (incl. `editable` flag)
- `database/migrations/`: Schema (driver-agnostic Laravel migrations)
- `Dockerfile`: Production image (FrankenPHP base; supervisord runs Octane :8000, Reverb :8080, `queue:work`, `schedule:work`; `etc/entrypoint.sh` migrates then caches config from runtime env)
- `Dockerfile.dev`, `docker-compose.yaml`: Dev setup (app + postgres 17 + queue worker + reverb)
- `phpstan.neon`, `.github/workflows/`: Larastan config and CI (PHP format/lint/test, JS format/lint/typecheck/build)
