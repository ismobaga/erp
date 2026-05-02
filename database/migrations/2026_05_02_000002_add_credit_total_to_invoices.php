<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a dedicated credit_total column to invoices.
 *
 * Previously refreshCreditBalance() reused discount_total to store credit note
 * reductions, silently destroying any real commercial discount.  This column
 * separates the two concepts:
 *
 *   discount_total  – user-entered commercial discount (immutable after saving)
 *   credit_total    – computed sum of approved credit notes applied to this invoice
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('credit_total', 15, 2)->default(0)->after('discount_total');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('credit_total');
        });
    }
};
