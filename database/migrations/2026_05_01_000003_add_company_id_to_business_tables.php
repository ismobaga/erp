<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Business tables that require a company_id foreign key.
     * All use non-nullable cascadeOnDelete.
     */
    private array $tables = [
        'clients',
        'services',
        'quotes',
        'quote_items',
        'invoices',
        'invoice_items',
        'payments',
        'expenses',
        'projects',
        'notes',
        'attachments',
        'sequences',
        'activity_logs',
        'financial_periods',
        'ledger_accounts',
        'journal_entries',
        'journal_entry_lines',
        'credit_notes',
        'recurring_invoices',
        'report_schedules',
        'dunning_logs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->foreignId('company_id')
                    ->constrained('companies')
                    ->cascadeOnDelete();
            });
        }

        // sequences uniqueness now also includes company_id
        Schema::table('sequences', function (Blueprint $table): void {
            $table->dropUnique(['key', 'period']);
            $table->unique(['company_id', 'key', 'period'], 'sequences_company_key_period_unique');
        });
    }

    public function down(): void
    {
        // Restore sequences unique constraint first
        Schema::table('sequences', function (Blueprint $table): void {
            $table->dropUnique('sequences_company_key_period_unique');
            $table->unique(['key', 'period']);
        });

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropConstrainedForeignId('company_id');
            });
        }
    }
};
