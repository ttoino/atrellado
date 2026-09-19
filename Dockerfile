# Multi-stage build for the app container: vite assets, composer vendor,
# then a lean php-fpm + nginx runtime. Built by wrangler on deploy.

FROM node:24-alpine AS assets
WORKDIR /app
RUN npm install -g pnpm@latest
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile
COPY . .
RUN pnpm run build

FROM composer:2 AS vendor
WORKDIR /app
# Cloudflare WARP MITMs local builds; the CA bundle is committed for that
# case and simply goes unused on Cloudflare's build infrastructure.
COPY etc/certs/warp.pem /etc/ssl/warp.pem
ENV SSL_CERT_FILE=/etc/ssl/warp.pem \
    CURL_CA_BUNDLE=/etc/ssl/warp.pem
RUN git config --global --add safe.directory /app
COPY composer.json composer.lock ./
# Extensions are checked in the runtime stage; the composer image lacks gd.
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-req=ext-gd
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM php:8.5-fpm AS runtime
# curl, mbstring and opcache are already compiled into php:8.5-fpm;
# re-installing those breaks docker-php-ext-install.
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        libpng-dev libjpeg62-turbo-dev libwebp-dev \
        libzip-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd intl bcmath zip

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY etc/nginx.conf /etc/nginx/conf.d/default.conf
COPY etc/entrypoint.sh /entrypoint.sh
COPY etc/php-overrides.ini /usr/local/etc/php/conf.d/atrellado.ini
RUN rm -f /etc/nginx/sites-enabled/default \
    && chmod +x /entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
