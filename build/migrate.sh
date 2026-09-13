#!/usr/bin/env bash
# Apply the Laravel migrations to D1. artisan cannot reach D1 (the
# binding only exists inside the worker), so wrangler applies a sqlite
# dump produced from the migrations via artisan schema:dump.
# Usage: build/migrate.sh [--local | --remote]
set -euo pipefail
cd "$(dirname "$0")/.."

MODE="${1:---local}"
if [[ "$MODE" != "--local" && "$MODE" != "--remote" ]]; then
	echo "usage: $0 [--local | --remote]" >&2
	exit 1
fi

SCRATCH_DIR="$(mktemp -d /tmp/atrellado-schema-XXXXXX)"
SCRATCH="$SCRATCH_DIR/db.sqlite"
touch "$SCRATCH"
trap 'rm -rf "$SCRATCH_DIR"' EXIT

# 1. Run the migrations on a scratch sqlite file and dump the result
# (schema + migrations table rows, mirroring schema:dump output).

docker run --rm \
	-v "$PWD:/app" \
	-v "$SCRATCH_DIR:$SCRATCH_DIR" \
	-w /app \
	-e DB_CONNECTION=sqlite \
	-e DB_DATABASE="$SCRATCH" \
	-e APP_KEY=base64:BWnmzjCDcGdqubv2l/n1UCjBAbJQKvrwcSZvDgdV95Q= \
	composer:2 php artisan migrate --database=sqlite --force

docker run --rm \
	-v "$PWD:/app" \
	-v "$SCRATCH_DIR:$SCRATCH_DIR" \
	-w /app \
	-e SCRATCH="$SCRATCH" \
	composer:2 php -r '
		$db = new PDO("sqlite:".getenv("SCRATCH"));
		$out = "";
		foreach ($db->query("SELECT sql FROM sqlite_master WHERE sql IS NOT NULL AND name NOT LIKE \"sqlite_%\" ORDER BY rowid") as $r) {
			$out .= $r["sql"].";\n";
		}
		foreach ($db->query("SELECT migration, batch FROM migrations ORDER BY id") as $r) {
			$out .= "INSERT INTO migrations (migration, batch) VALUES (\"{$r["migration"]}\", {$r["batch"]});\n";
		}
		file_put_contents("build/sqlite-schema.sql", $out);
	'

# 2. Wipe existing tables (the old hand-ported schema conflicts with
# the migration dump).
DROPS="$(mktemp /tmp/atrellado-drops-XXXXXX.sql)"
trap 'rm -f "$SCRATCH" "$DROPS"' EXIT
npx wrangler d1 execute atrellado-db "$MODE" --json \
	--command="SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE '\_cf\_%' ESCAPE '\'" \
	| node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>{const r=JSON.parse(d);const rows=r.flatMap(x=>x.results??[]).reverse();console.log(rows.map(t=>\"DROP TABLE IF EXISTS \\\"\"+t.name+\"\\\";\").join('\n'))})" \
	> "$DROPS"
if [[ -s "$DROPS" ]]; then
	npx wrangler d1 execute atrellado-db "$MODE" --file="$DROPS" --yes
fi

# 3. Apply the migration dump.
npx wrangler d1 execute atrellado-db "$MODE" --file=build/sqlite-schema.sql --yes
