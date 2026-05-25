<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('recurring_invoice_id')
                ->nullable()
                ->after('quote_id')
                ->constrained('recurring_invoices')
                ->nullOnDelete();

            $table->unique(
                ['company_id', 'recurring_invoice_id', 'issue_date'],
                'invoices_company_recurring_issue_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_company_recurring_issue_unique');
            $table->dropConstrainedForeignId('recurring_invoice_id');
        });
    }
};
