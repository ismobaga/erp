#!/bin/sh
set -e

rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
php artisan package:discover --ansi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port=8000
