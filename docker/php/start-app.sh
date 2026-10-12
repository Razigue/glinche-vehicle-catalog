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

if ! php artisan vehicles:sync; then
    echo "Initial vehicle synchronization failed, run 'php artisan vehicles:sync' once the API is reachable." >&2
fi

touch /tmp/ready

exec php-fpm
