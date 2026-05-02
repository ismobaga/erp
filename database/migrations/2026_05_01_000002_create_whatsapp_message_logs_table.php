<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->nullableMorphs('sendable');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone');
            $table->string('type')->default('text');
            $table->text('message')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending');
            $table->string('gowa_message_id')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
    }
};
