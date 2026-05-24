<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'recurring_invoice_id')) {
                $table->foreignId('recurring_invoice_id')
                    ->nullable()
                    ->after('quote_id')
                    ->constrained('recurring_invoices')
                    ->nullOnDelete();
            }
        });

        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasIndex('invoices', 'invoices_company_recurring_issue_unique')) {
                $table->unique(
                    ['company_id', 'recurring_invoice_id', 'issue_date'],
                    'invoices_company_recurring_issue_unique',
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (Schema::hasIndex('invoices', 'invoices_company_recurring_issue_unique')) {
                $table->dropUnique('invoices_company_recurring_issue_unique');
            }

            if (Schema::hasColumn('invoices', 'recurring_invoice_id')) {
                $table->dropForeign(['recurring_invoice_id']);
                $table->dropColumn('recurring_invoice_id');
            }
        });
    }
};
