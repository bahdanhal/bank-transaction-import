FROM php:8.5-fpm-alpine AS app

RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY . .

RUN rm -f bootstrap/cache/*.php \
    && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --classmap-authoritative --no-dev \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

USER www-data
EXPOSE 9000
