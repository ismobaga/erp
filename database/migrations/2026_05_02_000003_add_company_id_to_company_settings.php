<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add company_id to company_settings so that settings are properly scoped
 * per tenant.  The HasCompanyScope trait was added to CompanySetting, which
 * requires this column to exist.
 *
 * Existing rows are left with company_id = null (nullable foreign key)
 * so that pre-migration single-tenant installations continue to work.
 * Once companies are set up administrators should assign company_id to
 * the existing settings row.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();

            $table->index('company_id', 'company_settings_company_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
