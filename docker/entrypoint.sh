#!/bin/sh
set -e

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required."
    exit 1
fi

if [ -z "${DB_PASSWORD:-}" ]; then
    echo "DB_PASSWORD is required."
    exit 1
fi

rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
php artisan package:discover --ansi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec php artisan serve --host=0.0.0.0 --port=8000
