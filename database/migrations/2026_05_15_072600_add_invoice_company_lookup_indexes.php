<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasIndex('invoices', ['company_id', 'client_id'])) {
                $table->index(['company_id', 'client_id'], 'invoices_company_client_index');
            }

            if (! Schema::hasIndex('invoices', ['company_id', 'status'])) {
                $table->index(['company_id', 'status'], 'invoices_company_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (Schema::hasIndex('invoices', 'invoices_company_client_index')) {
                $table->dropIndex('invoices_company_client_index');
            }

            if (Schema::hasIndex('invoices', 'invoices_company_status_index')) {
                $table->dropIndex('invoices_company_status_index');
            }
        });
    }
};
