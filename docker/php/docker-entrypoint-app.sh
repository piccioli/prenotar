#!/bin/sh
set -e
cd /var/www/html

ensure_dirs() {
    mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
}

if [ "$(id -u)" = "0" ]; then
    ensure_dirs
    # public/ serve per `storage:link` e `filament:optimize` (symlink in public)
    chown -R www-data:www-data storage bootstrap/cache public || true
    exec gosu www-data "$0" "$@"
fi

ensure_dirs
php artisan storage:link --force --no-interaction 2>/dev/null || true

if [ "${AUTORUN_MIGRATIONS:-0}" = "1" ]; then
    php artisan migrate --force --no-interaction
fi

if [ "${AUTORUN_OPTIMIZE:-1}" = "1" ]; then
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
    php artisan event:cache --no-interaction
    php artisan filament:optimize --no-interaction
fi

exec docker-php-entrypoint "$@"
