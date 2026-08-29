FROM composer:2.8 AS composer

WORKDIR /build

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

FROM node:22-alpine AS node

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM serversideup/php:8.4-fpm-nginx AS app

USER root

COPY --from=composer /build/vendor /var/www/html/vendor
COPY --from=node /build/public/build /var/www/html/public/build
COPY --from=node /build/bootstrap/ssr /var/www/html/bootstrap/ssr
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD php artisan health:check || exit 1
