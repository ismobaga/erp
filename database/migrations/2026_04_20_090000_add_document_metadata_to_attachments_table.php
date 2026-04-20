<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            if (!Schema::hasColumn('attachments', 'category')) {
                $table->string('category')->nullable()->after('file_name');
            }

            if (!Schema::hasColumn('attachments', 'size_bytes')) {
                $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            if (Schema::hasColumn('attachments', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('attachments', 'size_bytes')) {
                $table->dropColumn('size_bytes');
            }
        });
    }
};
