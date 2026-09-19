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
- **API resources**: `app/Http/Resources/` serializes the comment/thread models for JSON, adding the request-dependent `editable` flag (the models themselves carry no auth-dependent appends).
- **Uploads**: profile pictures are bounded at 4000×4000 px and converted to webp in `app/Helpers/Files.php`; the decoded bitmaps are freed immediately after resampling.

## Code Style

- **PHP**: Laravel Pint, default preset.
- **Commits**: lowercase imperative summaries.
- **Comments**: rationale only, never narrative; at most three lines.

## Important Files

- `app/Observers/`: Eloquent observers (business triggers)
- `app/Http/Resources/`: JSON serialization (incl. `editable` flag)
- `app/Helpers/Files.php`: Bounded image conversion (webp)
- `database/migrations/`: Schema (driver-agnostic Laravel migrations)
- `docker-compose.yaml`, `Dockerfile`, `etc/`: The original Docker dev setup
