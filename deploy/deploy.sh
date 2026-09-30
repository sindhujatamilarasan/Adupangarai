#!/usr/bin/env bash
# Deploy the latest code. First time: deploy/deploy.sh --first (also loads ingredients, recipes and the demo account).
set -euo pipefail
cd "$(dirname "$0")/.."
compose() { docker compose -f docker-compose.prod.yml "$@"; }

[ -f .env ] || { echo "Missing .env (copy .env.example)"; exit 1; }
[ -f api/.env ] || { echo "Missing api/.env (copy api/.env.production.example)"; exit 1; }
grep -q '^APP_KEY=base64' api/.env || { echo "APP_KEY is empty in api/.env. Run: docker compose -f docker-compose.prod.yml run --rm --no-deps --entrypoint php api artisan key:generate --show"; exit 1; }

git pull --ff-only
# Migrations run when the api container starts; --wait returns once it is healthy.
compose up -d --build --remove-orphans --wait
if [ "${1:-}" = "--first" ] || [ "${1:-}" = "--seed" ]; then
  compose exec -T api php artisan db:seed --force
fi
docker image prune -f >/dev/null
echo "Deployed. Check: curl -fsS https://$(grep ^DOMAIN= .env | cut -d= -f2)/up"
