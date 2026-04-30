<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('portal_token')->nullable()->unique()->after('status');
        });

        // Backfill existing clients with a portal token.
        DB::table('clients')->whereNull('portal_token')->lazyById()->each(function ($client) {
            DB::table('clients')
                ->where('id', $client->id)
                ->update(['portal_token' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('portal_token');
        });
    }
};
