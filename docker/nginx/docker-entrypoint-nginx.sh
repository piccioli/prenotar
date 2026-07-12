#!/bin/sh
set -eu

mkdir -p /var/www/certbot/.well-known/acme-challenge

DOMAIN="${CERTBOT_DOMAIN:-}"
if [ -n "$DOMAIN" ] && [ -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
    for f in /etc/nginx/conf.d/10-ssl-redirect.conf /etc/nginx/conf.d/50-ssl-app.conf; do
        rm -f "$f"
    done
    # Porta HTTPS pubblica (mappata da Docker sul container 443, es. 8443 per develop): se diversa
    # da 443/vuota va nel redirect HTTP->HTTPS, altrimenti nginx punterebbe alla 443 di un altro stack.
    PORT_SUFFIX=""
    if [ -n "${CERTBOT_PUBLIC_HTTPS_PORT:-}" ] && [ "${CERTBOT_PUBLIC_HTTPS_PORT}" != "443" ]; then
        PORT_SUFFIX=":${CERTBOT_PUBLIC_HTTPS_PORT}"
    fi
    sed -e "s|__DOMAIN__|${DOMAIN}|g" -e "s|__HTTPS_PORT_SUFFIX__|${PORT_SUFFIX}|g" \
        /opt/prenotar-nginx/ssl-redirect.conf.template \
        > /etc/nginx/conf.d/10-ssl-redirect.conf
    sed "s|__DOMAIN__|${DOMAIN}|g" /opt/prenotar-nginx/ssl-app.conf.template \
        > /etc/nginx/conf.d/50-ssl-app.conf
else
    rm -f /etc/nginx/conf.d/10-ssl-redirect.conf /etc/nginx/conf.d/50-ssl-app.conf 2>/dev/null || true
fi

exec /docker-entrypoint.sh "$@"
