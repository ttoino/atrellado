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
[workers-php](https://github.com/ttoino/php-wasm-worker): PHP 8.5 in
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
`atrellado.toino.pt` is onboarded to Cloudflare Email Sending.

```bash
pnpm install
npm run dev             # build assets + vendor + bundle, wrangler dev
npm run migrate:local   # artisan migrate inside wrangler dev (port 8799)
# npm run migrate:remote && npm run deploy
```

Until workers-php is published to npm it is referenced as a sibling
checkout (`file:../php-wasm-worker/packages/workers-php`); clone
php-wasm-worker next to this repo.
