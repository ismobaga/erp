---
name: make
description: Artisan make commands for CROMMIX ERP — generate models, Filament resources, migrations, policies, jobs, and more.
---

# Make – CROMMIX ERP Generators

Use Artisan and Filament CLI generators to scaffold new code. Always prefer generators over writing boilerplate manually.

## Filament resources (most common)

```bash
# Full Filament resource (Resource + Pages + Form + Table)
php artisan make:filament-resource ModelName --generate

# With soft deletes support
php artisan make:filament-resource ModelName --generate --soft-deletes

# Simple resource (no sub-pages)
php artisan make:filament-resource ModelName --simple
```

Resources go in `app/Filament/Resources/`. After generating, add it to the appropriate Filament panel in `app/Providers/`.

## Models

```bash
# Model only
php artisan make:model ModelName

# Model + migration + factory + seeder + policy
php artisan make:model ModelName -mfsp

# Model + migration + controller + resource
php artisan make:model ModelName -mcr
```

## Other generators

```bash
# Migration
php artisan make:migration create_table_name_table

# Policy (for Spatie permissions)
php artisan make:policy ModelNamePolicy --model=ModelName

# Job
php artisan make:job JobName

# Mail
php artisan make:mail MailClassName

# Notification
php artisan make:notification NotificationName

# Observer
php artisan make:observer ModelNameObserver --model=ModelName

# Service class (no generator — create manually in app/Services/)
```

## Project conventions

- Models → `app/Models/`
- Policies → `app/Policies/` (register in `AuthServiceProvider` or via auto-discovery)
- Filament resources → `app/Filament/Resources/`
- Filament pages → `app/Filament/Pages/`
- Filament widgets → `app/Filament/Widgets/`
- Company-scoped models must use the `BelongsToCompany` concern (check existing models)
- Always add Spatie permission checks to new Filament resources
