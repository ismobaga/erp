<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_billing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('tenant_plans')->nullOnDelete();
            // e.g. subscription_created, invoice_paid, trial_started, plan_upgraded, payment_failed
            $table->string('event_type');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('FCFA');
            // pending | completed | failed
            $table->string('status')->default('completed');
            $table->string('external_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_billing_events');
    }
};
