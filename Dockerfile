# Multi-stage build for the app container: vite assets, composer vendor,
# then a FrankenPHP runtime. Built by wrangler on deploy.

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

FROM dunglas/frankenphp:1-php8.5-bookworm AS runtime
# install-php-extensions skips extensions that are already compiled in.
RUN install-php-extensions gd intl bcmath zip opcache \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Plain HTTP on the container's default port; TLS ends at the edge.
ENV SERVER_NAME=:8080

WORKDIR /app
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY etc/entrypoint.sh /entrypoint.sh
COPY etc/php-overrides.ini /usr/local/etc/php/conf.d/atrellado.ini
RUN chmod +x /entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
