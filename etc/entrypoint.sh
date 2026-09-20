#!/bin/bash
set -e

cd /var/www

if [ -z "$APP_KEY" ]; then
    echo "FATAL: APP_KEY must be provided via the container environment" >&2
    exit 1
fi

# Runtime env becomes the cached config. Only the service with
# RUN_MIGRATIONS=true (web) migrates; role services just boot.
php artisan storage:link || true
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi
php artisan config:cache
php artisan event:cache

# Housekeeping above runs as root (storage:link writes to public/); the
# service itself runs as www-data, which owns storage/ and bootstrap/cache.
exec setpriv --reuid=www-data --regid=www-data --init-groups "$@"
