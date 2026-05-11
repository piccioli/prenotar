#!/bin/sh
set -e
cd /var/www/html

if [ "$(id -u)" = "0" ]; then
    mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache || true
    exec gosu www-data "$0" "$@"
fi

exec "$@"
