<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original migration created a global unique constraint on ledger_accounts.code.
 * After multi-tenancy was introduced (company_id column added), two companies sharing
 * the same account code (e.g. 1010) would violate the constraint.
 *
 * This migration replaces the global unique with a per-company composite unique
 * so each tenant can have its own independent chart of accounts.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code'], 'ledger_accounts_company_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table): void {
            $table->dropUnique('ledger_accounts_company_id_code_unique');
            $table->unique('code');
        });
    }
};
