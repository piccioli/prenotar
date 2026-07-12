#!/bin/sh
# Rinnovo certificati (entrambi i domini, stesso volume condiviso) + reload Nginx.
# Adatto a cron (es. 2 volte al giorno). Uso dalla root del repo: ./scripts/certbot-renew.sh
set -eu
ROOT=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
cd "$ROOT"

docker compose -p prenotar -f docker-compose.production.yml --profile tools run --rm certbot renew \
    --webroot --webroot-path=/var/www/certbot --quiet

docker compose -p prenotar -f docker-compose.production.yml exec nginx nginx -s reload

# Lo stack develop potrebbe non essere attivo (es. macchina appena reinstallata): non bloccare il rinnovo.
if docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop ps -q nginx >/dev/null 2>&1; then
    docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop exec nginx nginx -s reload || true
fi
