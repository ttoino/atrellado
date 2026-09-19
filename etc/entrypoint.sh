#!/bin/bash
set -e

cd /var/www

if [ -z "$APP_KEY" ]; then
    echo "FATAL: APP_KEY must be provided via the container environment" >&2
    exit 1
fi

# Runtime env becomes the cached config; migrations run before traffic.
php artisan storage:link || true
php artisan migrate --force
php artisan config:cache
php artisan event:cache

exec supervisord -c /etc/supervisor/conf.d/atrellado.conf
