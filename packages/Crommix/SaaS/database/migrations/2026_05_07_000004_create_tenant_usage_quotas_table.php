<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_usage_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Metric key from saas.quota_metrics config.
            $table->string('metric');
            $table->unsignedBigInteger('used')->default(0);
            // NULL means unlimited.
            $table->unsignedBigInteger('limit')->nullable();
            // monthly | yearly | lifetime
            $table->string('period')->default('monthly');
            // When the counter should next be reset (NULL = never / lifetime).
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_usage_quotas');
    }
};
