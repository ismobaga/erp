#!/usr/bin/env bash
# Boots a local copy of the ERP inside Docker. Safe to run on every start:
# each step only does work when something is missing or out of date.
set -euo pipefail
cd /app

if [ ! -f .env ]; then
    echo "▶ Creating .env from .env.example"
    cp .env.example .env
fi

echo "▶ PHP dependencies"
composer install --no-interaction --prefer-dist --no-progress

if ! grep -qE '^APP_KEY=.+' .env; then
    php artisan key:generate --force
fi

echo "▶ Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}"
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -q; do sleep 1; done

php artisan config:clear >/dev/null
php artisan migrate --force

# Seed only an empty database (first start, or after `down -v`).
USERS=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -tAc 'select count(*) from users')
if [ "$USERS" = "0" ]; then
    echo "▶ Seeding roles, company and demo data (empty database)"
    php artisan db:seed --force
    php artisan db:seed --class='Database\Seeders\LocalDevelopmentSeeder' --force
fi

php artisan storage:link >/dev/null 2>&1 || echo "  (storage:link skipped — uploaded images may not display)"
php artisan optimize:clear >/dev/null

cat <<MSG

  ✔ ERP running on http://localhost:${APP_PORT:-8000}
      Admin  : http://localhost:${APP_PORT:-8000}/admin       admin@example.com / password
      Blog   : http://localhost:${APP_PORT:-8000}/blog-admin  (same login)
      Demo   : admin@demo.erp / DemoPass!123

MSG

exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
