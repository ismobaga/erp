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

### Start PostgreSQL with Docker Compose

```bash
docker compose up -d
```

Default database credentials:

- Host: `127.0.0.1`
- Port: `5432`
- Database: `erp`
- Username: `postgres`
- Password: `postgres`

### Run the application

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

## API layer and integrations

- OpenAPI JSON: `GET /api/docs/openapi.json`
- Public-scope endpoints (token auth): `/api/v1/public/*`
- Private-scope endpoints (token auth): `/api/v1/private/*`
- Webhook ingestion endpoint: `POST /api/v1/public/webhooks/{source}`
- All authenticated API requests are written to `activity_logs` as `api_request`.
- API webhook events are stored in `api_webhook_events`.

## Tests

```bash
php artisan test --filter=BillingRulesTest
```
