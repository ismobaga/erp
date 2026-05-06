<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('chat_id'); // WhatsApp JID of the contact/group
            $table->string('contact_name')->nullable(); // Display name from WhatsApp
            $table->string('status')->default('open'); // open | closed
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'chat_id']);
            $table->index(['company_id', 'status']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
