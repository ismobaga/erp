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

### Deploy with Docker Compose / Dokploy

```bash
docker compose up -d
```

By default this starts:

- `app` on `http://localhost:8000`
- `postgres` on the internal Docker network (not published to host)

Set these Dokploy environment variables before deployment:

- `APP_KEY` (required)
- `DB_DATABASE` (optional, default: `erp`)
- `DB_USERNAME` (optional, default: `postgres`)
- `DB_PASSWORD` (required)
- `RUN_MIGRATIONS` (optional, default: `false`; when `true`, migrations run on each container start)

Database connection values used by the app container:

- Host: `postgres`
- Port: `5432`
- Database: `${DB_DATABASE:-erp}`
- Username: `${DB_USERNAME:-postgres}`
- Password: `${DB_PASSWORD}`

### Run the application locally without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Access Filament at `/admin`.

## Business rules configuration

Key ERP rules are now centralized in [config/erp.php](config/erp.php) and can be overridden through environment variables:

- `ERP_INVOICE_DEFAULT_DUE_DAYS`
- `ERP_INVOICE_OVERDUE_GRACE_DAYS`
- `ERP_QUOTE_ACCEPTANCE_GRACE_DAYS`
- `ERP_EXPENSE_AUTO_APPROVE_LIMIT`
- `ERP_APPROVAL_BULK_LIMIT`
- `ERP_PROJECT_AUTO_APPROVE_STATUSES`

## Tests

```bash
php artisan test --filter=BillingRulesTest
```
