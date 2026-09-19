# AGENTS.md

## Project Overview

A Laravel 13 project-management app (kanban boards, task groups, tags, threads, notifications) backed by PostgreSQL. The original database triggers are implemented as Eloquent observers (`app/Observers/`).

**Type**: Laravel 13 + PHP 8.5
**Package Manager**: pnpm 12 (JS) + Composer 2 (PHP)

## Commands

- `composer install`: PHP dependencies
- `pnpm install && npm run build:assets`: Frontend assets (Vite)
- `php artisan`: Usual Laravel CLI
- `php artisan migrate`: Run migrations
- `php artisan test`: PHPUnit/Pest suite
- `./vendor/bin/pint`: Code style fixer

## Architecture Notes

- **Observers**: `app/Observers/` holds the Eloquent observers that replace the original plpgsql triggers, keeping the business rules portable across database drivers.
- **Uploads**: profile pictures are bounded at 4000×4000 px and converted to webp in `app/Helpers/Files.php`; the decoded bitmaps are freed immediately after resampling.

## Code Style

- **PHP**: Laravel Pint, default preset.
- **Commits**: lowercase imperative summaries.
- **Comments**: rationale only, never narrative; at most three lines.

## Deployment (this branch)

This branch deploys the app as a **Cloudflare Container** behind the worker `atrellado`:

- `Dockerfile` builds a php-fpm + nginx image (vite assets and composer vendor in earlier stages); `etc/entrypoint.sh` migrates and caches config/routes/views at boot; the health endpoint is `/ping` through php-fpm.
- `worker.ts` serves `/storage/*` straight from the R2 binding and forwards everything else to the container (`getContainer(env.CONTAINER, "atrellado")`).
- `do.ts` declares `AtrelladoContainer` and its static `outboundByHost` map: the container reaches bindings through plain-HTTP magic hosts — `http://d1.app` (D1 query/exec protocol of `App\Support\D1\D1HttpClient`), `http://r2.app` (bucket ops of `App\Support\R2\HttpR2Adapter`), `http://mail.app` (structured send of `App\Support\Mailer\HttpMailTransport`).
- Container env comes from `envVars` on the class (worker secrets + endpoint URLs), not from `.env`; the worker secret is `APP_KEY`.
- Resources are all named `atrellado`: D1 database, R2 bucket, worker. Cache is the `database` driver on D1 (see the cache-table migration), sessions are cookie-only, mail goes through the `send_email` binding.
- Commands: `npm run dev:worker` (local container in Docker), `npm run deploy`, `npm run cf-typegen`.

## Important Files

- `app/Observers/`: Eloquent observers (business triggers)
- `app/Helpers/Files.php`: Bounded image conversion (webp)
- `database/migrations/`: Schema (driver-agnostic Laravel migrations)
- `docker-compose.yaml`: The original Docker dev setup
- `Dockerfile`, `etc/`: The production container image (this branch)
