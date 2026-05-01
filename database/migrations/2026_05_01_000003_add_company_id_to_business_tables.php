<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * List of business tables that must be scoped per company.
     * Each entry maps a table name to whether a nullable FK is used.
     * All tables use cascadeOnDelete unless specified otherwise.
     */
    private array $tables = [
        'clients'           => false,
        'services'          => false,
        'quotes'            => false,
        'quote_items'       => false,
        'invoices'          => false,
        'invoice_items'     => false,
        'payments'          => false,
        'expenses'          => false,
        'projects'          => false,
        'notes'             => false,
        'attachments'       => false,
        'sequences'         => false,
        'activity_logs'     => false,
        'financial_periods' => false,
        'ledger_accounts'   => false,
        'journal_entries'   => false,
        'journal_entry_lines' => false,
        'credit_notes'      => false,
        'recurring_invoices' => false,
        'report_schedules'  => false,
        'dunning_logs'      => false,
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $nullable) {
            Schema::table($table, function (Blueprint $blueprint) use ($nullable): void {
                $col = $blueprint->foreignId('company_id');

                if ($nullable) {
                    $col->nullable();
                }

                $col->constrained('companies')->cascadeOnDelete();
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

        foreach (array_keys($this->tables) as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropConstrainedForeignId('company_id');
            });
        }
    }
};
