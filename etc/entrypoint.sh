#!/bin/sh
set -e
cd /var/www/html

# Open the port immediately — the runtime health check stops containers
# whose port stays closed, and migrations over HTTP are slow. /ping stays
# 503 until the ready flag exists, so no traffic arrives before boot ends.
rm -f /tmp/atrellado-ready
php-fpm -D
nginx -g 'daemon off;' &
NGINX=$!

# Bring the schema up to date and bake config, routes and views from the
# environment injected by the worker.
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

touch /tmp/atrellado-ready
wait $NGINX
