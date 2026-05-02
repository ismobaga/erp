<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('frequency')->comment('daily, weekly, monthly, quarterly, yearly'); // daily, weekly, monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('next_due_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('net_days')->default(30); // payment terms
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->json('items')->nullable(); // snapshot of line items
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
