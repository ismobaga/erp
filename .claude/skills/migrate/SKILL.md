---
name: migrate
description: Run Laravel database migrations for CROMMIX ERP. Handles migrate, rollback, fresh, and seed operations.
---

# Migrate – CROMMIX ERP

Run Artisan migration commands against the database.

## Common commands

```bash
# Run pending migrations
php artisan migrate

# Rollback the last batch
php artisan migrate:rollback

# Rollback N steps
php artisan migrate:rollback --step=3

# Fresh install (drop all tables + migrate)
php artisan migrate:fresh

# Fresh + seed with demo data
php artisan migrate:fresh --seed

# Seed without migrating
php artisan db:seed

# Run a specific seeder
php artisan db:seed --class=CompanySeeder

# Check migration status
php artisan migrate:status
```

## Caution

- **`migrate:fresh` is destructive** — it drops ALL tables. Never run on production.
- Always confirm with the user before running `migrate:fresh` or `migrate:rollback` on a shared database.
- In production use `migrate` only (never `fresh` or `--seed`).

## Creating new migrations

```bash
# New table
php artisan make:migration create_table_name_table

# Add column to existing table
php artisan make:migration add_column_to_table_name_table --table=table_name
```

Migrations live in `database/migrations/`. Follow the existing naming pattern: `YYYY_MM_DD_HHMMSS_description`.

## Seeders

- Seeders live in `database/seeders/`
- `DatabaseSeeder.php` is the main entry point
- Company-scoped data should always be seeded with a valid `company_id`
