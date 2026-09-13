#!/usr/bin/env bash
# Run artisan migrate inside the worker against its D1 binding (artisan
# cannot reach D1 directly — the binding only exists in the worker).
# Usage: build/migrate.sh [--local | --remote]
set -euo pipefail
cd "$(dirname "$0")/.."

MODE="${1:---local}"
KEY="$(grep '^WORKERS_MIGRATE_KEY=' .env | cut -d= -f2)"

case "$MODE" in
	--local)  URL="http://localhost:8799/_workers/migrate" ;;
	--remote) URL="https://atrellado.toino.workers.dev/_workers/migrate" ;;
	*) echo "usage: $0 [--local | --remote]" >&2; exit 1 ;;
esac

curl -sf -X POST "$URL" --data-urlencode "key=$KEY"
echo
