<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes on company-scoped tables for the most common query
 * patterns (listing, status filtering, date-range reporting).
 *
 * These complement the individual company_id foreign-key index added by the
 * 2026_05_01_000003_add_company_id_to_business_tables migration.
 */
return new class extends Migration {
    public function up(): void
    {
        // Invoices: overdue queries + status dashboard + date-range reports
        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['company_id', 'status', 'due_date'], 'invoices_company_status_due_date_index');
            $table->index(['company_id', 'issue_date'], 'invoices_company_issue_date_index');
        });

        // Payments: revenue summary queries
        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['company_id', 'payment_date'], 'payments_company_payment_date_index');
        });

        // Journal entries: period-based GL reports
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->index(['company_id', 'status', 'entry_date'], 'journal_entries_company_status_date_index');
        });

        // Activity logs: audit trail views scoped by company
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->index(['company_id', 'action', 'created_at'], 'activity_logs_company_action_date_index');
        });

        // Expenses: approval workflow + date range reports
        Schema::table('expenses', function (Blueprint $table): void {
            $table->index(['company_id', 'approval_status', 'expense_date'], 'expenses_company_status_date_index');
        });

        // Quotes: status + issue date listing
        Schema::table('quotes', function (Blueprint $table): void {
            $table->index(['company_id', 'status', 'issue_date'], 'quotes_company_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_company_status_due_date_index');
            $table->dropIndex('invoices_company_issue_date_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_company_payment_date_index');
        });

        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropIndex('journal_entries_company_status_date_index');
        });

        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex('activity_logs_company_action_date_index');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_company_status_date_index');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropIndex('quotes_company_status_date_index');
        });
    }
};
