# CROMMIX ERP Internal (v1 Foundation)

Internal-only ERP foundation for **CROMMIX MALI S.A.** built with:

- Laravel
- Filament
- PostgreSQL (default in `.env.example`)
- `spatie/laravel-permission`
- Local file storage + database queue

## Implemented in this repository

- Laravel application bootstrap
- Filament admin panel provider
- Role/permission package setup and seed blueprint
- ERP core schema migrations for:
  - company settings
  - clients
  - services
  - quotes + quote items
  - invoices + invoice items
  - payments
  - expenses
  - projects
  - attachments
  - activity logs
- User schema extended with `phone`, `status`, `last_login_at`
- Core billing business rules in models:
  - quote/invoice totals derived from line items
  - invoice `paid_total` / `balance_due` auto-refresh from payments
  - invoice status auto-updates (`paid`, `partially_paid`, `overdue`)
  - payment positivity and overpayment guard (unless explicitly allowed)

## Roles seeded

- Super Admin
- Admin
- Finance
- Project Manager
- Staff
- Read Only

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Access Filament at `/admin`.

## Tests

```bash
php artisan test --filter=BillingRulesTest
```
