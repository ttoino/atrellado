#!/bin/sh
set -e
cd /var/www/html

# Every container boot brings the schema up to date and bakes config,
# routes and views from the environment injected by the worker.
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

php-fpm -D
exec nginx -g 'daemon off;'
