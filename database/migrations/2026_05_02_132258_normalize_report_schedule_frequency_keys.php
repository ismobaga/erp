<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('report_schedules')
            ->where('frequency', 'Quotidienne')
            ->update(['frequency' => 'daily']);

        DB::table('report_schedules')
            ->where('frequency', 'Hebdomadaire')
            ->update(['frequency' => 'weekly']);

        DB::table('report_schedules')
            ->where('frequency', 'Mensuelle')
            ->update(['frequency' => 'monthly']);
    }

    public function down(): void
    {
        DB::table('report_schedules')
            ->where('frequency', 'daily')
            ->update(['frequency' => 'Quotidienne']);

        DB::table('report_schedules')
            ->where('frequency', 'weekly')
            ->update(['frequency' => 'Hebdomadaire']);

        DB::table('report_schedules')
            ->where('frequency', 'monthly')
            ->update(['frequency' => 'Mensuelle']);
    }
};
