#!/bin/sh
set -eu

mkdir -p /var/www/certbot/.well-known/acme-challenge

DOMAIN="${CERTBOT_DOMAIN:-}"
if [ -n "$DOMAIN" ] && [ -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
    for f in /etc/nginx/conf.d/10-ssl-redirect.conf /etc/nginx/conf.d/50-ssl-app.conf; do
        rm -f "$f"
    done
    sed "s|__DOMAIN__|${DOMAIN}|g" /opt/prenotar-nginx/ssl-redirect.conf.template \
        > /etc/nginx/conf.d/10-ssl-redirect.conf
    sed "s|__DOMAIN__|${DOMAIN}|g" /opt/prenotar-nginx/ssl-app.conf.template \
        > /etc/nginx/conf.d/50-ssl-app.conf
else
    rm -f /etc/nginx/conf.d/10-ssl-redirect.conf /etc/nginx/conf.d/50-ssl-app.conf 2>/dev/null || true
fi

exec /docker-entrypoint.sh "$@"
