FROM dunglas/frankenphp:1-php8.5 AS base

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        libgd-dev libwebp-dev libjpeg-dev libfreetype6-dev \
        libpq-dev libsqlite3-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype \
    && docker-php-ext-install gd pdo_pgsql zip intl pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# FrankenPHP docs' production baseline; app overrides land in conf.d.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer/composer:2-bin /composer /usr/bin/composer

FROM node:22 AS assets

WORKDIR /app
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN corepack enable pnpm && pnpm install --frozen-lockfile
COPY . .
RUN pnpm run build

FROM base

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-autoloader --no-scripts

COPY . .
COPY --from=assets /app/public/build public/build

# Secrets and environment come from the container runtime, never the image.
RUN composer dumpautoload --optimize \
    && rm -f .env .env.* \
    && chown -R www-data:www-data storage bootstrap/cache

COPY ./etc/php/php.ini /usr/local/etc/php/conf.d/php.ini
COPY ./etc/entrypoint.sh /entrypoint.sh

# The base image's healthcheck probes the Caddy admin port, which only the
# web role runs; compose.production.yaml defines the web healthcheck.
HEALTHCHECK NONE

EXPOSE 8000
EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000", "--workers=auto", "--max-requests=500"]
