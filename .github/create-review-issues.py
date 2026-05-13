#!/usr/bin/env python3
"""
Creates all production-readiness review issues in the ismobaga/erp repository.

Usage:
    pip install requests
    GH_TOKEN=ghp_yourtoken python3 .github/create-review-issues.py

The script is idempotent: it skips issues whose title already exists in the repo.
"""

from __future__ import annotations

import os
import sys
import time
import textwrap
import requests

REPO = "ismobaga/erp"
TOKEN = os.environ.get("GH_TOKEN") or os.environ.get("GITHUB_TOKEN")

if not TOKEN:
    print("ERROR: Set GH_TOKEN or GITHUB_TOKEN environment variable.")
    sys.exit(1)

HEADERS = {
    "Authorization": f"token {TOKEN}",
    "Accept": "application/vnd.github+json",
    "X-GitHub-Api-Version": "2022-11-28",
}

# ---------------------------------------------------------------------------
# Issue definitions
# ---------------------------------------------------------------------------
ISSUES: list[dict] = [
    # ── SECURITY ─────────────────────────────────────────────────────────────
    {
        "title": "[Security][Critical] File signature validation silently broken — single-quoted \\x escapes",
        "body": textwrap.dedent("""\
            ## Summary

            In `SecureFileUploadService::validateFileSignature()`, magic byte strings are
            defined using **single-quoted** PHP strings. PHP does **not** interpret `\\x`
            escape sequences in single quotes. The `strpos()` check never matches, so
            **every file passes signature validation regardless of its actual type**.

            ## Location

            `app/Services/SecureFileUploadService.php` — `validateFileSignature()` method.

            ## Broken Code

            ```php
            // Single quotes — PHP does NOT expand \\x here
            $signatures = [
                'pdf' => '\\x25\\x50\\x44\\x46',  // NOT %PDF — it is a 16-char ASCII string
                'jpg' => '\\xFF\\xD8\\xFF',
                'png' => '\\x89\\x50\\x4E\\x47',
                'zip' => '\\x50\\x4B\\x03\\x04',
            ];
            ```

            ## Fix

            ```php
            $signatures = [
                'pdf'  => "\\x25\\x50\\x44\\x46",  // %PDF
                'jpg'  => "\\xFF\\xD8\\xFF",
                'jpeg' => "\\xFF\\xD8\\xFF",
                'png'  => "\\x89\\x50\\x4E\\x47\\x0D\\x0A\\x1A\\x0A",
                'zip'  => "\\x50\\x4B\\x03\\x04",
            ];
            ```

            ## Severity

            **Critical** — file upload security is completely bypassed. Any file type
            can be uploaded by using an allowed extension.

            ## Recommended Test

            ```php
            public function test_file_with_wrong_magic_bytes_is_rejected(): void {
                // An EXE disguised as a PDF — magic bytes are MZ not %PDF
                $file = UploadedFile::fake()->createWithContent('malware.pdf', "\\x4D\\x5A\\x90\\x00");
                $this->expectException(ValidationException::class);
                app(SecureFileUploadService::class)->storeFile($file, 'invoice', 1, 1, 1);
            }
            ```
        """),
        "labels": ["bug", "security"],
    },
    {
        "title": "[Security][High] SSRF via DomPDF isRemoteEnabled in PDF controllers",
        "body": textwrap.dedent("""\
            ## Summary

            All PDF controllers configure DomPDF with `'isRemoteEnabled' => true`. If
            invoice notes, descriptions, or client-supplied fields contain HTML with
            external URLs (e.g. `<img src="http://169.254.169.254/latest/meta-data/">`),
            DomPDF makes a server-side HTTP request during rendering — a **Server-Side
            Request Forgery (SSRF)** vulnerability.

            ## Affected Files

            - `app/Http/Controllers/InvoicePdfController.php`
            - `app/Http/Controllers/ClientPortalController.php` (`downloadPdf`)
            - `app/Http/Controllers/QuotePdfController.php`
            - `app/Http/Controllers/PaymentPdfController.php`
            - `app/Http/Controllers/ExpensePdfController.php`
            - `app/Http/Controllers/CreditNotePdfController.php`

            ## Fix

            ```php
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,   // ← was true
                'dpi'                  => 120,
                'defaultFont'          => 'DejaVu Sans',
            ])
            ```

            Bundle CSS and fonts as local assets. The company logo is already handled via
            `ResolvesLogoDataUri` — extend that pattern to all remote assets.

            ## Severity

            **High** — exploitable by any user with permission to edit invoice content,
            and by any client through portal fields if HTML is rendered unsanitised.
        """),
        "labels": ["bug", "security"],
    },
    {
        "title": "[Security][High] Open redirect in client portal language switcher",
        "body": textwrap.dedent("""\
            ## Summary

            `ClientPortalController::setLanguage()` redirects to the raw `Referer` header
            without any validation. An attacker can craft a link that redirects portal
            users to any external URL.

            ## Location

            `app/Http/Controllers/ClientPortalController.php` — `setLanguage()`.

            ## Vulnerable Code

            ```php
            $referer = $request->headers->get('referer');
            return $referer
                ? redirect($referer)            // ← Referer: https://evil.com/phishing
                : redirect()->route('portal.index', ['token' => $token]);
            ```

            ## Fix

            ```php
            $referer = $request->headers->get('referer');
            $safe = $referer && str_starts_with($referer, url('/')) ? $referer : null;
            return $safe
                ? redirect($safe)
                : redirect()->route('portal.index', ['token' => $token]);
            ```

            ## Severity

            **High** — phishing vector targeting clients who interact with the portal.
        """),
        "labels": ["bug", "security"],
    },
    {
        "title": "[Security][Medium] Health diagnostics endpoint is publicly accessible",
        "body": textwrap.dedent("""\
            ## Summary

            `GET /health/diagnostics` exposes internal operational state (queue health,
            backup status, failed job counts, open alerts) with no authentication.

            ## Location

            `routes/web.php` lines 36-38.

            ## Fix

            ```php
            Route::get('/health/diagnostics', [HealthCheckController::class, 'diagnostics'])
                ->middleware(['auth', 'throttle:30,1'])
                ->name('health.diagnostics');
            ```

            Or restrict by IP for monitoring tool access.

            ## Severity

            **Medium** — information disclosure; reveals internal system state to
            unauthenticated actors.
        """),
        "labels": ["bug", "security"],
    },
    {
        "title": "[Security][Medium] trustProxies set to wildcard '*' — IP spoofing risk",
        "body": textwrap.dedent("""\
            ## Summary

            `bootstrap/app.php` trusts all proxy IPs (`at: '*'`). Any client can spoof
            their IP via `X-Forwarded-For`, bypassing IP-based rate limiting and
            corrupting audit log source IPs.

            ## Location

            `bootstrap/app.php` line 21.

            ## Fix

            ```php
            // Restrict to your actual reverse-proxy CIDR ranges
            $middleware->trustProxies(at: '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16');
            ```

            ## Severity

            **Medium** — rate limiting and audit trail integrity can be bypassed.
        """),
        "labels": ["bug", "security"],
    },
    {
        "title": "[Security][Medium] CSP allows unsafe-inline scripts — XSS protection undermined",
        "body": textwrap.dedent("""\
            ## Summary

            The `Content-Security-Policy` header includes `'unsafe-inline'` in
            `script-src`, which negates most of the XSS protection that CSP provides.

            ## Location

            `app/Http/Middleware/SetSecurityHeaders.php` line 21.

            ## Current

            ```
            script-src 'self' 'unsafe-inline' cdn.jsdelivr.net
            ```

            ## Fix

            Migrate to nonce-based CSP:

            1. Generate a cryptographically random nonce per request.
            2. Include `'nonce-{value}'` in `script-src`.
            3. Add `nonce="{{ csp_nonce() }}"` to all inline `<script>` tags.
            4. Move any remaining inline scripts to external `.js` files.

            ## Severity

            **Medium** — substantially reduces effective XSS protection.
        """),
        "labels": ["bug", "security"],
    },
    {
        "title": "[Security][Medium] Unvalidated reason parameter in portal quote rejection",
        "body": textwrap.dedent("""\
            ## Summary

            `ClientPortalController::rejectQuote()` writes the raw `reason` request
            input directly into `meta_json` in audit logs without any validation or
            length restriction.

            ## Location

            `app/Http/Controllers/ClientPortalController.php` — `rejectQuote()`.

            ## Fix

            ```php
            $validated = $request->validate([
                'reason' => ['nullable', 'string', 'max:1000'],
            ]);

            app(AuditTrailService::class)->log('portal_quote_rejected', $quote, [
                'reason' => $validated['reason'] ?? null,
                // ...
            ]);
            ```

            ## Severity

            **Medium** — unbounded input can bloat the database and is a best-practice
            violation.
        """),
        "labels": ["bug", "security"],
    },

    # ── MULTI-TENANCY ─────────────────────────────────────────────────────────
    {
        "title": "[Multi-tenancy][High] invoices:send-due-reminders processes all tenants without company binding",
        "body": textwrap.dedent("""\
            ## Summary

            The `invoices:send-due-reminders` Artisan command queries `Invoice::query()`
            in console context without binding `currentCompany`. `HasCompanyScope` only
            logs a warning in console — it does **not** restrict results. The command
            therefore processes every tenant's invoices in a single run with no
            per-company isolation.

            ## Location

            `routes/console.php` — `invoices:send-due-reminders` definition.

            ## Fix

            Follow the pattern already used in `quotes:expire-due`:

            ```php
            Company::query()->where('is_active', true)->each(
                function (Company $company) use ($targetDate): void {
                    app()->instance('currentCompany', $company);

                    Invoice::query()
                        ->with('client')
                        ->whereDate('due_date', $targetDate)
                        ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
                        ->where('balance_due', '>', 0)
                        ->get()
                        ->each(function (Invoice $invoice) { /* send reminder */ });
                }
            );
            ```

            ## Severity

            **High** — cross-tenant data processing; prevents per-company notification
            preferences from being respected.
        """),
        "labels": ["bug", "multi-tenancy"],
    },
    {
        "title": "[Multi-tenancy][High] DunningService::eligibleInvoices() loads all overdue invoices into memory",
        "body": textwrap.dedent("""\
            ## Summary

            `DunningService::eligibleInvoices()` calls `->get()` on all overdue invoices
            (no limit) and filters in PHP. For a large tenant this loads thousands of
            records with their relations into memory.

            ## Location

            `app/Services/DunningService.php` — `eligibleInvoices()`.

            ## Fix

            Push eligibility filtering to the database using a subquery on `dunning_logs`,
            and process in chunks:

            ```php
            Invoice::query()
                ->where('status', 'overdue')
                ->whereNotNull('due_date')
                ->with(['client'])
                ->chunkById(100, function (Collection $chunk): void {
                    $chunk->each(fn (Invoice $invoice) => $this->processEligible($invoice));
                });
            ```

            ## Severity

            **High** — memory exhaustion risk on large tenants; directly affects the
            `erp:run-dunning` scheduled command.
        """),
        "labels": ["bug", "multi-tenancy", "performance"],
    },
    {
        "title": "[Multi-tenancy][Low] Portal resolveCompany() falls back to first active company instead of aborting",
        "body": textwrap.dedent("""\
            ## Summary

            `ClientPortalController::resolveCompany()` caches and returns the first active
            company when `client->company_id` is null. This would render the wrong
            company's branding, bank details, and legal name to a client.

            ## Location

            `app/Http/Controllers/ClientPortalController.php` — `resolveCompany()`.

            ## Fix

            ```php
            protected function resolveCompany(Client $client): Company
            {
                if ($client->company_id === null) {
                    abort(404);
                }
                return Company::findOrFail($client->company_id);
            }
            ```

            ## Severity

            **Low** — guarded by FK constraint on `clients.company_id`, but should be
            hardened against direct DB inserts or migration gaps.
        """),
        "labels": ["bug", "multi-tenancy"],
    },

    # ── PERFORMANCE ──────────────────────────────────────────────────────────
    {
        "title": "[Performance][High] N+1 queries: FinancialPeriod lock status checked per row in Filament invoice table",
        "body": textwrap.dedent("""\
            ## Summary

            `InvoiceResource::lockStatusLabel()` and `lockStatusColor()` call
            `FinancialPeriod::isDateLocked($record->issue_date)` for every row rendered
            in the Filament table. Each call executes a DB query. On a 50-row page
            this generates **50 extra queries**.

            ## Location

            `app/Filament/Resources/Invoices/InvoiceResource.php` — `lockStatusLabel()`
            and `lockStatusColor()`.

            ## Fix

            Cache the closed period date ranges once per request in a static variable
            and do the date-range check in PHP:

            ```php
            private static function closedPeriodRanges(): array
            {
                static $cache = null;
                return $cache ??= FinancialPeriod::query()
                    ->closed()
                    ->select('starts_on', 'ends_on')
                    ->get()
                    ->map(fn ($p) => [$p->starts_on->toDateString(), $p->ends_on->toDateString()])
                    ->all();
            }

            private static function isRecordLocked(Model $record): bool
            {
                $date = $record instanceof Invoice
                    ? $record->issue_date?->toDateString()
                    : null;
                if ($date === null) {
                    return false;
                }
                foreach (static::closedPeriodRanges() as [$start, $end]) {
                    if ($date >= $start && $date <= $end) {
                        return true;
                    }
                }
                return false;
            }
            ```

            ## Severity

            **High** — O(n) DB queries per page load; degrades significantly as data grows.
        """),
        "labels": ["bug", "performance"],
    },
    {
        "title": "[Performance][High] Unbounded .get() calls in client portal — no pagination",
        "body": textwrap.dedent("""\
            ## Summary

            `ClientPortalController` fetches every record with `->get()` across all
            portal pages: invoices, quotes, tickets, projects, conversations, and
            documents. A client with thousands of records causes a full table load on
            every portal page visit.

            ## Affected Methods

            - `index()` — all invoices
            - `quotes()` — all quotes
            - `tickets()` — all support tickets
            - `projects()` — all projects
            - `conversations()` — all WhatsApp conversations + all messages
            - `documents()` — all documents across invoices, projects, and client

            ## Fix

            Apply `simplePaginate(25)` or `cursorPaginate(25)` to all portal list queries.
            The `activity()` method already uses `->limit(100)` — extend that discipline
            everywhere.

            ```php
            $invoices = Invoice::withoutCompanyScope()
                ->where('client_id', $client->id)
                ->orderByDesc('issue_date')
                ->simplePaginate(25);
            ```

            ## Severity

            **High** — memory exhaustion and slow response times for active clients.
        """),
        "labels": ["bug", "performance"],
    },
    {
        "title": "[Performance][High] Missing database indexes on frequently queried columns",
        "body": textwrap.dedent("""\
            ## Summary

            Several columns used in frequent queries lack indexes.

            ## Missing Indexes

            | Table | Column(s) | Used In |
            |---|---|---|
            | `clients` | `portal_token` | Every portal request — equality lookup |
            | `recurring_invoices` | `(company_id, is_active, next_due_date)` | Daily scheduler |
            | `journal_entries` | unique `(source_type, source_id)` | Ledger duplicate-check |
            | `payments` | `invoice_id` | Invoice JOIN queries |

            ## Fix

            Add a new migration:

            ```php
            // clients
            $table->index('portal_token');

            // recurring_invoices
            $table->index(
                ['company_id', 'is_active', 'next_due_date'],
                'ri_company_active_due_index'
            );

            // journal_entries — also prevents duplicate ledger entries (see related issue)
            $table->unique(
                ['source_type', 'source_id'],
                'journal_entries_source_unique'
            );
            ```

            ## Severity

            **High** — `portal_token` lookup runs on every portal request with no index;
            scheduler scans all recurring invoices sequentially.
        """),
        "labels": ["performance", "database"],
    },
    {
        "title": "[Performance][Medium] FinancialPeriod::ensureDateIsOpen() hits DB on every model save — no caching",
        "body": textwrap.dedent("""\
            ## Summary

            `Invoice`, `Expense`, and `Payment` call `FinancialPeriod::ensureDateIsOpen()`
            in their `saving` Eloquent hook. Each call runs a DB query. On bulk imports
            (e.g. 500 rows) this fires **500 queries** to check whether the same closed
            period exists.

            ## Location

            `app/Models/FinancialPeriod.php` — `findClosedFor()`.

            ## Fix

            Add a request-scoped static cache keyed on `companyId:dateString`:

            ```php
            private static array $lockCache = [];

            public static function findClosedFor(CarbonInterface|string|null $date): ?self
            {
                if (blank($date)) {
                    return null;
                }
                $companyId = app()->bound('currentCompany') ? app('currentCompany')->id : 'none';
                $key = $companyId . ':' . Carbon::parse($date)->toDateString();
                return static::$lockCache[$key] ??= static::query()->closed()->current($date)->first();
            }

            public static function flushLockCache(): void { static::$lockCache = []; }
            ```

            Call `flushLockCache()` in a `ServiceProvider::terminate()` to reset between
            requests.

            ## Severity

            **Medium** — noticeable on bulk operations; each save in a loop adds one DB
            round-trip.
        """),
        "labels": ["performance"],
    },
    {
        "title": "[Performance][Medium] Filament Select preload() loads entire relationship into memory on form open",
        "body": textwrap.dedent("""\
            ## Summary

            `InvoiceResource` and `QuoteResource` forms use `->preload()` on `client_id`
            and `quote_id` selects. This loads the **complete** relationship table into
            memory when the form is opened. For a company with thousands of clients or
            quotes this causes a significant page load delay.

            ## Location

            `app/Filament/Resources/Invoices/InvoiceResource.php` — `client_id` and
            `quote_id` select fields.

            ## Fix

            Remove `preload()` and rely solely on `searchable()`. Filament will load
            matching options via AJAX on each keystroke:

            ```php
            Select::make('client_id')
                ->relationship('client', 'company_name')
                ->searchable(['company_name', 'contact_name', 'email'])
                // ->preload()  ← remove
                ->required(),
            ```

            ## Severity

            **Medium** — large form load times for companies with many clients; memory
            spike on the server.
        """),
        "labels": ["performance", "filament"],
    },

    # ── ERP / ACCOUNTING ─────────────────────────────────────────────────────
    {
        "title": "[ERP][Critical] invoices:generate-recurring is scheduled but the command is never defined",
        "body": textwrap.dedent("""\
            ## Summary

            `routes/console.php` schedules `invoices:generate-recurring` to run daily
            at 06:00, but **no `Artisan::command('invoices:generate-recurring', ...)`
            definition exists anywhere in the codebase**.

            ## Location

            `routes/console.php` line 153.

            ## Impact

            The Laravel scheduler silently fails with "Command not found" every morning.
            **Recurring invoice generation is completely non-functional.** Clients on
            recurring billing plans receive no invoices.

            ## Fix

            Define the command with:
            - Per-company iteration (bind `currentCompany` for each active company).
            - Idempotency guard (check if an invoice for this period already exists).
            - Proper error handling and logging.

            See related issue: "No idempotency guard on recurring invoice generation".

            ## Severity

            **Critical** — core billing feature is silently broken.
        """),
        "labels": ["bug", "invoicing"],
    },
    {
        "title": "[ERP][High] No idempotency guard on recurring invoice generation",
        "body": textwrap.dedent("""\
            ## Summary

            Even once `invoices:generate-recurring` is implemented, there is no safeguard
            against duplicate invoice creation if the scheduler runs twice (deploy
            restart, clock skew) or if the job crashes between creating the invoice and
            advancing `next_due_date`.

            ## Location

            `app/Models/RecurringInvoice.php` — `advanceNextDueDate()`.

            ## Fix

            1. Add `recurring_invoice_id` FK column to `invoices`.
            2. Add unique constraint `(company_id, recurring_invoice_id, issue_date)`.
            3. Wrap creation and date advance in one atomic transaction with a pre-check:

            ```php
            DB::transaction(function () use ($ri): void {
                $alreadyGenerated = Invoice::query()
                    ->where('recurring_invoice_id', $ri->id)
                    ->whereDate('issue_date', $ri->next_due_date)
                    ->lockForUpdate()
                    ->exists();

                if (!$alreadyGenerated) {
                    Invoice::create([..., 'recurring_invoice_id' => $ri->id]);
                    $ri->advanceNextDueDate();
                }
            });
            ```

            ## Severity

            **High** — duplicate invoices sent to clients; accounting integrity issue.
        """),
        "labels": ["bug", "invoicing"],
    },
    {
        "title": "[ERP][High] refreshFinancials() executes after payment transaction commits — inconsistency risk",
        "body": textwrap.dedent("""\
            ## Summary

            `Payment::save()` wraps in `DB::transaction()`. The `saved` Eloquent event
            fires **after** the transaction commits. If `Invoice::refreshFinancials()`
            (called in the `saved` hook) throws an exception, the payment is permanently
            committed but the invoice's `paid_total`, `balance_due`, and `status` remain
            stale — the system is in an **inconsistent state**.

            ## Location

            `app/Models/Payment.php` — `booted()` `saved` hook.

            ## Fix

            Move `refreshFinancials()` inside the payment transaction using a service:

            ```php
            // app/Actions/ApplyPaymentAction.php
            class ApplyPaymentAction
            {
                public function execute(array $data): Payment
                {
                    return DB::transaction(function () use ($data): Payment {
                        $invoice = Invoice::whereKey($data['invoice_id'])
                            ->lockForUpdate()
                            ->firstOrFail();
                        $payment = Payment::create($data);
                        $invoice->refreshFinancials();
                        return $payment;
                    });
                }
            }
            ```

            Remove the `saved` hook from `Payment::booted()`.

            ## Severity

            **High** — invoice financial state can become permanently desynchronised from
            actual payments.
        """),
        "labels": ["bug", "accounting"],
    },
    {
        "title": "[ERP][High] No unique DB constraint on journal_entries(source_type, source_id) — duplicate ledger entries possible",
        "body": textwrap.dedent("""\
            ## Summary

            `LedgerPostingService` uses `SELECT … FOR UPDATE` to prevent duplicate journal
            entries. However, when **no row exists yet**, two concurrent requests both see
            `null`, both pass the check, and both call `JournalEntry::create()` — producing
            two journal entries for the same source document (double-posted revenue,
            double-posted tax).

            ## Location

            `app/Services/LedgerPostingService.php` — `createAndPost()`.
            `database/migrations/2026_04_21_100002_create_general_ledger_tables.php`.

            ## Fix

            Add a unique constraint at the database level:

            ```php
            // Migration
            $table->unique(['source_type', 'source_id'], 'journal_entries_source_unique');
            ```

            Then in `LedgerPostingService::createAndPost()`, catch the violation:

            ```php
            try {
                $entry = JournalEntry::create([...]);
            } catch (UniqueConstraintViolationException) {
                $entry = JournalEntry::query()
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->firstOrFail();
            }
            ```

            ## Severity

            **High** — double-posted ledger entries cause incorrect financial statements.
        """),
        "labels": ["bug", "accounting", "database"],
    },
    {
        "title": "[ERP][Medium] Credit note posting does not reverse Tax Payable — accounting integrity issue",
        "body": textwrap.dedent("""\
            ## Summary

            `LedgerPostingService::postCreditNote()` posts the revenue reversal
            (DR Sales Revenue / CR Accounts Receivable) but does **not** reverse the
            Tax Payable entry that was created when the original invoice was posted. The
            tax liability remains on the books after the revenue is reversed.

            ## Location

            `app/Services/LedgerPostingService.php` — `postCreditNote()`.

            ## Fix

            Add a `DR Tax Payable` (reversal) line when the credit note has a tax
            component:

            ```php
            if ($taxAmount > 0 && $taxAccount) {
                $lines[] = [
                    'account_id'  => $taxAccount->id,
                    'debit'       => $taxAmount,   // DR Tax Payable — reversal
                    'credit'      => 0,
                    'description' => 'Tax reversal: ' . $creditNote->credit_number,
                ];
                // Revenue debit = credit note total minus tax
                $lines[0]['debit'] = $creditNote->amount - $taxAmount;
            }
            ```

            ## Severity

            **Medium** — overstated tax liability on the balance sheet after credit notes.
        """),
        "labels": ["bug", "accounting"],
    },
    {
        "title": "[ERP][Medium] Dunning log written before email is dispatched — silent failures possible",
        "body": textwrap.dedent("""\
            ## Summary

            `DunningService::runAutomatedDunning()` writes the dunning log record to the
            database **before** dispatching the email. If the queue driver is down, the
            log permanently records the reminder as "sent" — preventing retry for
            `MIN_DAYS_BETWEEN_SAME_STAGE` days while the client never received the
            reminder.

            ## Location

            `app/Services/DunningService.php` — `runAutomatedDunning()`.

            ## Fix

            Log the reminder after a successful queue dispatch, or use a two-step
            status approach:

            ```php
            $log = DunningLog::create([..., 'status' => 'dispatched']);
            try {
                Mail::to($client->email)->queue(new InvoiceReminderMail($invoice));
                $log->forceFill(['status' => 'queued'])->save();
            } catch (\\Throwable $e) {
                $log->forceFill(['status' => 'failed', 'error' => $e->getMessage()])->save();
            }
            ```

            ## Severity

            **Medium** — clients may silently miss payment reminders while the system
            believes they were contacted.
        """),
        "labels": ["bug"],
    },
    {
        "title": "[ERP][Medium] No optimistic locking on Invoice::refreshFinancials() — race condition under concurrent payments",
        "body": textwrap.dedent("""\
            ## Summary

            Two simultaneous payment saves for the same invoice both call
            `refreshFinancials()`. The last `saveQuietly()` call overwrites the first —
            one payment's contribution to `paid_total` is lost, leaving the invoice in a
            stale state.

            ## Location

            `app/Models/Invoice.php` — `refreshFinancials()`.

            ## Fix

            Acquire a `SELECT FOR UPDATE` row lock before reading financial state:

            ```php
            public function refreshFinancials(): void
            {
                DB::transaction(function (): void {
                    $fresh = Invoice::whereKey($this->getKey())->lockForUpdate()->first();
                    $paidTotal = (float) $fresh->payments()->sum('amount');
                    $balanceDue = max(0, (float) $fresh->total - $paidTotal);
                    $fresh->forceFill(['paid_total' => $paidTotal, 'balance_due' => $balanceDue])->saveQuietly();
                });
            }
            ```

            ## Severity

            **Medium** — race condition under concurrent payment recording; harder to
            reproduce but real in production.
        """),
        "labels": ["bug", "concurrency"],
    },
    {
        "title": "[ERP][Medium] Payment::save() override creates unintended nested transactions",
        "body": textwrap.dedent("""\
            ## Summary

            `Payment::save()` overrides the parent with a `DB::transaction()` wrapper.
            When called from within an existing transaction (e.g.
            `reconcileAgainstOpenInvoice()`), PostgreSQL creates a savepoint. The
            `SELECT FOR UPDATE` on the invoice row in the `saving` hook acquires a weaker
            lock within the savepoint, reducing the effectiveness of the concurrency
            guard.

            ## Location

            `app/Models/Payment.php` — `save()` override.

            ## Fix

            Remove the `DB::transaction()` override from `Payment::save()`. Move
            transaction management to a service/action class (`ApplyPaymentAction`) that
            wraps the full operation: lock invoice → validate → save payment → refresh
            financials.

            ## Severity

            **Medium** — subtle correctness issue; reduces the effectiveness of
            concurrency guards.
        """),
        "labels": ["bug", "concurrency"],
    },

    # ── ARCHITECTURE ──────────────────────────────────────────────────────────
    {
        "title": "[Architecture][Medium] Fat Invoice model — financial calculation logic belongs in service layer",
        "body": textwrap.dedent("""\
            ## Summary

            The `Invoice` Eloquent model contains significant business logic:
            `recalculateTotals()`, `refreshCreditBalance()`, `refreshFinancials()`,
            `convertToInvoice()`. The `booted()` hook calls `InvoiceNumberService` and
            `AuditTrailService`. The `saved` payment hook calls `refreshFinancials()`
            which calls `saveQuietly()` which triggers `saving` again — **re-entrant hook
            risk**.

            ## Recommended Refactoring

            ```
            App\\Services\\InvoiceService
            ├── recalculateTotals(Invoice)
            ├── refreshFinancials(Invoice)
            └── issue(Invoice, ?int $userId)

            App\\Actions\\ApplyPaymentAction
            App\\Actions\\ConvertQuoteToInvoiceAction
            ```

            Use Laravel Events (`InvoiceIssued`, `PaymentRecorded`) to decouple side
            effects (ledger posting, audit trail) from the model lifecycle. Models
            should be pure data holders + relationships.

            ## Severity

            **Medium** — works today but becomes increasingly difficult to maintain and
            test as the domain grows.
        """),
        "labels": ["enhancement", "architecture"],
    },
    {
        "title": "[Architecture][Medium] No API Resource classes — raw model serialization exposes internal fields",
        "body": textwrap.dedent("""\
            ## Summary

            All API controllers return raw Eloquent model serialization
            (`response()->json($invoices)`). This exposes internal fields
            (`created_by`, `updated_by`, `company_id`, raw timestamps) that API
            consumers don't need, makes breaking changes in refactors invisible to
            consumers, and complicates field-level access control.

            ## Affected Files

            All controllers in `app/Http/Controllers/Api/V1/Private/`.

            ## Fix

            Create `JsonResource` classes with explicit field allowlists:

            ```php
            class InvoiceResource extends JsonResource
            {
                public function toArray(Request $request): array
                {
                    return [
                        'id'             => $this->id,
                        'invoice_number' => $this->invoice_number,
                        'issue_date'     => $this->issue_date->toIso8601String(),
                        'status'         => $this->status,
                        'total'          => (float) $this->total,
                        'balance_due'    => (float) $this->balance_due,
                        'client'         => new ClientResource($this->whenLoaded('client')),
                    ];
                }
            }
            ```

            ## Severity

            **Medium** — information disclosure of internal fields; breaking refactors
            will silently affect API consumers.
        """),
        "labels": ["enhancement", "architecture", "api"],
    },
    {
        "title": "[Architecture][Medium] No Form Request classes for API input validation",
        "body": textwrap.dedent("""\
            ## Summary

            All API controllers accept raw `Request` objects with minimal or no input
            validation. Filter parameters (`status`, date ranges, `per_page`) are
            unvalidated or only partially checked.

            ## Fix

            Create `FormRequest` classes:

            ```php
            class ListInvoicesRequest extends FormRequest
            {
                public function rules(): array
                {
                    return [
                        'per_page' => ['integer', 'min:1', 'max:100'],
                        'status'   => ['nullable', Rule::in(InvoiceStateMachine::STATUSES)],
                        'from'     => ['nullable', 'date'],
                        'to'       => ['nullable', 'date', 'after_or_equal:from'],
                    ];
                }
            }
            ```

            ## Severity

            **Medium** — no immediate security risk (scoped queries prevent data leaks),
            but poor maintainability and missing input sanitisation.
        """),
        "labels": ["enhancement", "architecture", "api"],
    },
    {
        "title": "[Architecture][Low] No Laravel Policies — authorization scattered across controllers and views",
        "body": textwrap.dedent("""\
            ## Summary

            Authorization uses bare permission string checks scattered across controllers,
            Filament resources, and Blade views:

            ```php
            abort_unless(auth()->user()?->canAny(['invoices.view', 'reports.view']), 403);
            ```

            Laravel Policies provide a single authoritative place for authorization logic
            per model, enable record-level access control, and integrate cleanly with
            `@can` Blade directives.

            ## Recommendation

            Create Policy classes for core models and register in `AuthServiceProvider`:

            ```php
            class InvoicePolicy
            {
                public function view(User $user, Invoice $invoice): bool
                {
                    return $user->can('invoices.view')
                        && $user->companies->contains($invoice->company_id);
                }
            }
            ```

            ## Severity

            **Low** — works as-is; harder to audit and extend; second-layer company
            isolation relies solely on `HasCompanyScope`.
        """),
        "labels": ["enhancement", "architecture"],
    },

    # ── LARAVEL BEST PRACTICES ───────────────────────────────────────────────
    {
        "title": "[Laravel][Medium] erp:prune-audit-logs uses single unbounded DELETE — table lock risk",
        "body": textwrap.dedent("""\
            ## Summary

            `erp:prune-audit-logs` runs one `DELETE … WHERE created_at < ?` with no
            chunking. On large installations (millions of rows) this single query locks
            the `activity_logs` table for its full duration — causing replication lag,
            slow queries, and potential timeout errors.

            ## Location

            `routes/console.php` — `erp:prune-audit-logs` command.

            ## Fix

            ```php
            do {
                $deleted = ActivityLog::query()
                    ->where('created_at', '<', now()->subDays($retentionDays))
                    ->limit(1000)
                    ->delete();
                if ($deleted > 0) {
                    usleep(100_000); // 100 ms pause between batches
                }
            } while ($deleted > 0);
            ```

            ## Severity

            **Medium** — production table lock risk on large installations.
        """),
        "labels": ["bug", "performance"],
    },

    # ── FILAMENT ─────────────────────────────────────────────────────────────
    {
        "title": "[Filament][Medium] Inline business logic in table action callbacks — extract to service classes",
        "body": textwrap.dedent("""\
            ## Summary

            Filament table action callbacks in `InvoiceResource` contain substantial
            inline logic: email dispatch, WhatsApp sending, audit logging, and
            notification display. This makes the resource class hard to test and violates
            SRP.

            ## Location

            `app/Filament/Resources/Invoices/InvoiceResource.php` — `sendReminder`,
            `sendWhatsapp`, `sendWhatsappReminder` action definitions.

            ## Fix

            Extract each action to a dedicated class:

            ```php
            // InvoiceResource.php
            Action::make('sendReminder')
                ->action(fn (Invoice $record) => app(SendInvoiceReminderAction::class)->execute($record))

            // app/Actions/SendInvoiceReminderAction.php
            class SendInvoiceReminderAction
            {
                public function execute(Invoice $invoice): void
                {
                    // validate, dispatch, audit, notify
                }
            }
            ```

            ## Severity

            **Medium** — non-trivial actions cannot currently be unit-tested in isolation.
        """),
        "labels": ["enhancement", "filament"],
    },
    {
        "title": "[Filament][Low] Quote::find($state) in form callback should use findOrFail()",
        "body": textwrap.dedent("""\
            ## Summary

            `InvoiceResource` form's `quote_id` select uses `Quote::find($state)` with
            silent null handling in the `afterStateUpdated` callback. Using `findOrFail()`
            would be more explicit and easier to debug.

            ## Location

            `app/Filament/Resources/Invoices/InvoiceResource.php` — `quote_id` select,
            `afterStateUpdated` callback.

            ## Fix

            ```php
            ->afterStateUpdated(function ($state, Set $set): void {
                if (!$state) return;
                $quote = Quote::findOrFail($state);
                $set('client_id', $quote->client_id);
            })
            ```

            Note: `HasCompanyScope` already prevents cross-company access.

            ## Severity

            **Low** — edge case; already guarded by company scope.
        """),
        "labels": ["enhancement", "filament"],
    },
]

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def get_existing_titles() -> set[str]:
    """Fetch all existing open issue titles to enable idempotent reruns."""
    titles: set[str] = set()
    page = 1
    while True:
        resp = requests.get(
            f"https://api.github.com/repos/{REPO}/issues",
            headers=HEADERS,
            params={"state": "open", "per_page": 100, "page": page},
            timeout=30,
        )
        resp.raise_for_status()
        items = resp.json()
        if not items:
            break
        for item in items:
            titles.add(item["title"])
        page += 1
    return titles


def create_issue(issue: dict) -> dict:
    resp = requests.post(
        f"https://api.github.com/repos/{REPO}/issues",
        headers=HEADERS,
        json=issue,
        timeout=30,
    )
    return {"status": resp.status_code, "data": resp.json()}


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> None:
    print(f"Fetching existing issues from {REPO}…")
    existing = get_existing_titles()
    print(f"  {len(existing)} existing open issues found.\n")

    created = 0
    skipped = 0
    failed = 0

    for idx, issue in enumerate(ISSUES, 1):
        title = issue["title"]
        if title in existing:
            print(f"  [{idx:02}/{len(ISSUES)}] SKIP (already exists): {title[:70]}")
            skipped += 1
            continue

        result = create_issue(issue)
        if result["status"] == 201:
            num = result["data"].get("number", "?")
            url = result["data"].get("html_url", "")
            print(f"  [{idx:02}/{len(ISSUES)}] ✓ Created #{num}: {title[:70]}")
            print(f"              {url}")
            created += 1
        else:
            msg = result["data"].get("message", "unknown error")
            print(f"  [{idx:02}/{len(ISSUES)}] ✗ FAILED ({result['status']}): {msg} — {title[:60]}")
            failed += 1

        time.sleep(1.2)  # Stay well under the GitHub REST rate limit

    print(f"\n{'='*60}")
    print(f"Created:  {created}")
    print(f"Skipped:  {skipped} (already existed)")
    print(f"Failed:   {failed}")


if __name__ == "__main__":
    main()
