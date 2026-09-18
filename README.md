# FEUP-LBAW-PROJ

Group project for the LBAW course unit at FEUP.

[Old readme](README.old.md)\
[Final website](https://atrellado.toino.pt/)

## Unit info

* **Name**: Laboratório de Bases de Dados e Aplicações Web (Database and Web Applications Laboratory)
* **Date**: Year 3, Semester 1, 2022/23
* [**More info**](https://sigarra.up.pt/feup/ucurr_geral.ficha_uc_view?pv_ocorrencia_id=501685)

## Disclaimer

This repository (and all others with the name format `feup-*`) are for archival and educational purposes only.

If you don't understand some part of the code or anything else in this repo, feel free to ask (although I may not understand it myself anymore).

Keep in mind that this repo is public. If you copy any code and use it in your school projects you may be flagged for plagiarism by automated tools.

## Cloudflare Workers deployment

This repo deploys as a Cloudflare Worker via
[workers-php](https://github.com/ttoino/workers-php): PHP 8.5 in
wasm, with Cloudflare D1 as the database (custom `d1` Laravel driver,
see `app/Providers/D1ServiceProvider.php`). The schema is driver-agnostic
Laravel migrations (the business triggers are Eloquent observers);
artisan cannot reach D1 outside the worker, so migrations run in-place:
`build/migrate.sh` curls a key-gated route that calls `artisan migrate`
against the D1 binding. Full-text search uses LIKE fallbacks instead of
tsvector/ts_rank. Uploaded files (profile pictures) live in an R2 bucket
(`FILES` binding): `FILESYSTEM_DRIVER=r2` via `app/Support/WorkersR2Adapter.php`,
served back under `/storage/` by a `staticRoutes` entry in `worker.ts`.
Mail goes out through a `send_email` binding (`EMAIL`) via
`app/Support/Mailer/WorkersEmailTransport.php`; the sender domain
`atrellado.toino.pt` is onboarded to Cloudflare Email Sending. The cache
is Workers KV (`CACHE` binding, `app/Support/WorkersKvStore.php`) so
rate limits hold across isolates.

## Memory: the 128 MiB isolate wall

Workers isolates cap at 128 MiB. `php-web.wasm` declares its linear
memory at exactly 128 MiB and accounting is touch-based: a cold PHP
boot plus one Laravel 13 request touches ~110–125 MB, so roughly one
heavy request (login with bcrypt, registration) fits per isolate.
Symptoms are 503s on fresh isolates; warm isolates serve fine.

Mitigations in place: `build/build.sh` bakes Laravel's config/route/view
caches at the runtime mount path (`/persist/app`) so light reads skip
most PHP parsing. Bcrypt stays at framework defaults. Vendor trimming
(`tests/`, `docs/`) was evaluated and rejected — unmounted files are
read lazily, so trimming shrinks the tarball but not per-request memory.

Measure locally with `grep VmRSS /proc/<workerd-pid>/status` across
requests; in production use the dashboard memory chart per version.

```bash
pnpm install
npm run dev             # build assets + vendor + bundle, wrangler dev
npm run migrate:local   # artisan migrate inside wrangler dev (port 8799)
# npm run migrate:remote && npm run deploy
```

Until workers-php is published to npm it is referenced as a sibling
checkout (`file:../workers-php/packages/workers-php`); clone
workers-php next to this repo.
