FROM serversideup/php:8.4-fpm-nginx

USER root

RUN install-php-extensions gd exif pdo_mysql bcmath

ENV PHP_OPCACHE_ENABLE="1"

USER www-data
