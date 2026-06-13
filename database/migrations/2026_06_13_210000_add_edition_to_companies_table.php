<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            // NULL = inherit from the global ERP_APP_EDITION env setting.
            // A non-null value overrides the global edition for this company only.
            $table->string('edition', 20)->nullable()->after('advanced_options');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('edition');
        });
    }
};
