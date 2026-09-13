#!/usr/bin/env bash
# Build atrellado for workers-php: frontend assets (vite), production
# vendor (dockerized composer — no local PHP toolchain), app tarball,
# and docroot statics staged next to it for ASSETS.
set -euo pipefail
cd "$(dirname "$0")/.."

COMPOSER_CACHE_DIR=${COMPOSER_CACHE_DIR:-$HOME/.cache/composer}
WARP_CA="$HOME/.local/share/cloudflare-warp-certs/CloudflareRootCertificateCombined.pem"
CA_ARGS=()
if [[ -f "$WARP_CA" ]]; then
	CA_ARGS=(-v "$WARP_CA:/warp-ca.pem:ro" -e SSL_CERT_FILE=/warp-ca.pem -e CURL_CA_BUNDLE=/warp-ca.pem)
fi

# 1. Frontend assets (vite build -> public/build).
npm run build:assets --silent

# 2. Production vendor. The lock predates PHP 8.5, so platform checks
# are skipped; the wasm runtime provides every extension the app needs.
docker run --rm \
	-v "$PWD:/app" \
	-v "$COMPOSER_CACHE_DIR:/tmp/composer-cache" \
	"${CA_ARGS[@]}" \
	-w /app composer:2 \
	composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# The container writes root-owned files; hand them back to the user.
docker run --rm -v "$PWD:/app" -w /app composer:2 \
	chown -R "$(id -u):$(id -g)" vendor composer.lock

# 3. Bundle the app (docroot is the web root; vendor ships in the tarball).
npx workers-php build ./ --out ./dist --docroot public --entrypoint index.php

# 4. Stage docroot statics for ASSETS (PHP files stay tarball-only).
tar -cf - -C public --exclude=index.php . | tar -xf - -C dist
