#!/bin/sh
set -e
cd /app

# Open the port immediately — the runtime health check stops containers
# whose port stays closed, and migrations over HTTP are slow. /ping stays
# 503 until the ready flag exists, so no traffic arrives before boot ends.
rm -f /tmp/atrellado-ready
frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile &
FRANKENPHP=$!

# sh as PID 1 does not forward signals; pass SIGTERM along so rollouts
# and sleepAfter stop the server gracefully instead of after SIGKILL.
trap 'kill -TERM $FRANKENPHP' TERM

# Bring the schema up to date and bake config, routes and views from the
# environment injected by the worker.
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

touch /tmp/atrellado-ready
wait $FRANKENPHP
