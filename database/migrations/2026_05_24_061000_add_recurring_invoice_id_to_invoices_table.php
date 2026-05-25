<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'recurring_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->foreignId('recurring_invoice_id')
                    ->nullable()
                    ->after('quote_id')
                    ->constrained('recurring_invoices')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasIndex('invoices', 'invoices_company_recurring_issue_unique', 'unique')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->unique(
                    ['company_id', 'recurring_invoice_id', 'issue_date'],
                    'invoices_company_recurring_issue_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('invoices', 'invoices_company_recurring_issue_unique', 'unique')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropUnique('invoices_company_recurring_issue_unique');
            });
        }

        if (Schema::hasColumn('invoices', 'recurring_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('recurring_invoice_id');
            });
        }
    }
};
