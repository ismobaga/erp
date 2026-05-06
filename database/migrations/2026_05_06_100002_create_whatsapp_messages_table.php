<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('message_id')->nullable(); // GoWA message ID
            $table->string('direction'); // inbound | outbound
            $table->string('event_type')->default('message'); // message | message.reaction | message.edited | message.revoked | message.deleted | call.offer
            $table->string('type')->default('text'); // text | image | document | audio | video | sticker | contact | location | reaction | revoked
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('from_jid')->nullable();
            $table->string('ack_status')->default('pending'); // pending | server | delivered | read | played
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'sent_at']);
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
