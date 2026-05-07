<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('scope', 16)->default('private');
            $table->string('token_hash', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'scope']);
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('api_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('api_token_id')->constrained('api_tokens')->cascadeOnDelete();
            $table->string('source', 100);
            $table->string('event', 150);
            $table->json('payload_json');
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['company_id', 'source', 'received_at'], 'api_webhook_events_company_source_received_idx');
            $table->index(['event', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_webhook_events');
        Schema::dropIfExists('api_tokens');
    }
};
