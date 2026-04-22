<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->boolean('is_flagged')->default(false)->after('allow_overpayment');
            $table->timestamp('flagged_at')->nullable()->after('is_flagged');
            $table->string('flagged_reason')->nullable()->after('flagged_at');
            $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete()->after('flagged_reason');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('flagged_by');
            $table->dropColumn(['is_flagged', 'flagged_at', 'flagged_reason']);
        });
    }
};
