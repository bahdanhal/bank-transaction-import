FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci || npm install
COPY . .
RUN npm run build

FROM php:8.5-fpm-alpine AS app
RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --classmap-authoritative --no-dev \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

USER www-data
EXPOSE 9000
