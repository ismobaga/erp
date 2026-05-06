<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('pos_sessions')->cascadeOnDelete();
            $table->string('order_number')->nullable();
            $table->string('status')->default('pending'); // pending, completed, refunded, cancelled
            $table->string('payment_method')->default('cash'); // cash, card, mobile, etc.
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('change_given', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
