<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table) {
            // Replace the global unique constraint on code with a per-company composite unique.
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code'], 'ledger_accounts_company_id_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->dropUnique('ledger_accounts_company_id_code_unique');
            $table->unique('code');
        });
    }
};
