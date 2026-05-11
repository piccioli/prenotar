# syntax=docker/dockerfile:1

FROM node:22-bookworm AS assets

WORKDIR /app

COPY package.json ./
RUN npm install --ignore-scripts

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM composer:2 AS vendor

ARG COMPOSER_INSTALL_DEV=0

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./

RUN if [ "$COMPOSER_INSTALL_DEV" = "1" ]; then \
      composer install --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs; \
    else \
      composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs; \
    fi

COPY . .

COPY --from=assets /app/public/build ./public/build

RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache/data \
    && if [ "$COMPOSER_INSTALL_DEV" = "1" ]; then \
      composer dump-autoload --optimize --no-interaction \
      && cp .env.production.example .env \
      && php artisan key:generate --force --no-interaction \
      && php artisan package:discover --ansi --no-interaction \
      && php artisan filament:upgrade --ansi --no-interaction || true \
      && rm -f .env; \
    else \
      composer dump-autoload --optimize --no-dev --no-interaction \
      && cp .env.production.example .env \
      && php artisan key:generate --force --no-interaction \
      && php artisan package:discover --ansi --no-interaction \
      && php artisan filament:upgrade --ansi --no-interaction || true \
      && rm -f .env; \
    fi

FROM php:8.4-fpm-bookworm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl git zip unzip gosu \
    libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl zip pdo_mysql bcmath opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-prenotar.ini
COPY docker/php/fpm-zzz-prenotar.conf /usr/local/etc/php-fpm.d/zzz-prenotar.conf

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

COPY docker/php/docker-entrypoint-app.sh /usr/local/bin/docker-entrypoint-app.sh
COPY docker/php/docker-entrypoint-worker.sh /usr/local/bin/docker-entrypoint-worker.sh

RUN chmod +x /usr/local/bin/docker-entrypoint-app.sh /usr/local/bin/docker-entrypoint-worker.sh \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER root

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-app.sh"]

CMD ["php-fpm"]

FROM nginx:1.27-alpine AS nginx

RUN apk add --no-cache wget

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1/up || exit 1
