#!/bin/sh
set -e

rm -f /tmp/ready

composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -qE '^APP_KEY=.+' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force

touch /tmp/ready

exec php-fpm
