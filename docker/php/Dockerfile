FROM php:8.4-fpm-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache shadow \
    && docker-php-ext-install pdo_mysql \
    && groupmod --gid "${GID}" www-data \
    && usermod --uid "${UID}" --gid "${GID}" www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY start-app.sh /usr/local/bin/start-app

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html

USER www-data
