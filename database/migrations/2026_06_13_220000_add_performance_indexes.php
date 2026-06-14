<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->index(['company_id', 'updated_at'], 'clients_company_updated_at_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'projects_company_status_index');
            $table->index(['company_id', 'status', 'due_date'], 'projects_company_status_due_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_company_updated_at_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_company_status_index');
            $table->dropIndex('projects_company_status_due_date_index');
        });
    }
};
