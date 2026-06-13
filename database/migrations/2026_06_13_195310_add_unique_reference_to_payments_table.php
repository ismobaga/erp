<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Prevent concurrent inserts from producing duplicate PAY-YYYYMMDD-NNNN
            // references within the same company. NULL references are excluded from
            // the constraint by SQL semantics and remain allowed.
            $table->unique(['company_id', 'reference'], 'payments_company_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_company_reference_unique');
        });
    }
};
