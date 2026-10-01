# CROMMIX ERP Internal (v1 Foundation)

Internal-only ERP foundation for **CROMMIX MALI S.A.** built with:

- Laravel
- Filament
- PostgreSQL (default in `.env.example`)
- `spatie/laravel-permission`
- Local file storage + database queue

## Long-term vision

CROMMIX ERP is being shaped as a **modular business operating system** for African enterprises, delivered through a **multi-tenant SaaS architecture**.

The platform roadmap converges key operating domains in one tenant-aware foundation:

- **Finance**: billing, accounting controls, reporting, and cash operations.
- **Communication**: client conversations, notifications, and channel integrations (including WhatsApp).
- **HR**: employee lifecycle, payroll-adjacent workflows, and compliance records.
- **Projects**: planning, delivery tracking, and cross-team execution visibility.
- **Customer management**: CRM, client portal interactions, and service continuity.
- **Operational workflows**: approvals, automation, monitoring, and auditability across modules.

This direction prioritizes composable modules, strict tenant isolation, and SaaS-readiness for reliable distribution at scale.

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

## Proposed next issues (prioritized)

The foundation and core ERP flows are in place. To align with the long-term modular SaaS vision, the following backlog is proposed for next iterations:

1. **P0 — End-to-end tenant safety test coverage**
   - Add/expand integration tests that verify tenant scoping across API, portal, and Filament resources.
   - Rationale: strict isolation is a core architecture promise and should remain protected as modules grow.

2. **P0 — Recurring billing operations hardening**
   - Improve monitoring/retry visibility around recurring invoice generation and scheduled billing jobs.
   - Rationale: billing reliability directly impacts revenue integrity and finance trust.

3. **P1 — API consumer experience improvements**
   - Extend OpenAPI examples and document practical client workflows for public/private endpoints.
   - Rationale: API surface exists; better onboarding reduces integration friction and support load.

4. **P1 — Client portal workflow completeness**
   - Prioritize missing self-service actions (document lifecycle, quote/invoice follow-up, ticket UX polish).
   - Rationale: portal maturity improves communication and service continuity outcomes.

5. **P2 — Cross-module analytics and export depth**
   - Expand KPI/export options for finance, projects, and communication modules.
   - Rationale: decision support value grows when module data is easier to compare and operationalize.

6. **P2 — Operational resilience playbooks**
   - Define runbooks/alert thresholds for health checks, backups, failed jobs, and recovery procedures.
   - Rationale: improves response quality as deployment scale and module count increase.

Team members: please comment on this list in the issue thread with:
- blockers or dependencies,
- implementation estimates,
- and any reprioritization proposals.

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
