<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_message_logs', function (Blueprint $table) {
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('company_id')
                ->constrained('whatsapp_conversations')
                ->nullOnDelete();
            $table->string('ack_status')->default('pending')->after('gowa_message_id'); // pending | server | delivered | read | played
            $table->timestamp('delivered_at')->nullable()->after('ack_status');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_message_logs', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn(['conversation_id', 'ack_status', 'delivered_at', 'read_at']);
        });
    }
};
