#!/bin/sh
# Prima emissione Let's Encrypt (webroot). Uso dalla root del repo:
#   ./scripts/certbot-certonly.sh prenotar.montagnaservizi.com tuo@email.it
#   ./scripts/certbot-certonly.sh prenotar.develop.montagnaservizi.com tuo@email.it develop
#
# Solo la produzione pubblica le porte 80/443 dell'host: la validazione HTTP-01 passa SEMPRE
# dal webroot dello stack produzione (volume condiviso `prenotar_shared_certbot_www`), anche
# per il dominio develop. Il terzo argomento opzionale ("develop") decide solo quale stack
# ricaricare a fine emissione, per applicare il certificato appena ottenuto.
set -eu
ROOT=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
cd "$ROOT"
DOMAIN="${1:?Dominio: $0 <FQDN> <email> [develop]}"
EMAIL="${2:?Email Let's Encrypt: $0 <FQDN> <email> [develop]}"
TARGET="${3:-production}"

docker compose -p prenotar -f docker-compose.production.yml up -d nginx

docker compose -p prenotar -f docker-compose.production.yml --profile tools run --rm certbot certonly \
    --webroot --webroot-path=/var/www/certbot \
    -d "$DOMAIN" \
    --email "$EMAIL" \
    --agree-tos \
    --non-interactive

if [ "$TARGET" = "develop" ]; then
    CERTBOT_DOMAIN="$DOMAIN" docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop \
        up -d --force-recreate nginx
    echo "Imposta in .env.develop anche APP_URL=https://$DOMAIN (e verifica HTTPS_PUBLISH=8443), poi se serve:"
    echo "  docker compose -p prenotar-develop -f docker-compose.develop.yml --env-file .env.develop exec app php artisan config:clear"
else
    CERTBOT_DOMAIN="$DOMAIN" docker compose -p prenotar -f docker-compose.production.yml up -d --force-recreate nginx
    echo "Imposta in .env anche APP_URL=https://$DOMAIN (e SESSION_SECURE_COOKIE=true in produzione), poi se serve:"
    echo "  docker compose -p prenotar -f docker-compose.production.yml exec app php artisan config:clear"
fi
