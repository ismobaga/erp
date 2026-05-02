<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dunning_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('stage'); // 1 | 2 | 3 | final
            $table->string('channel')->default('email'); // email | sms | phone | letter
            $table->timestamp('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['invoice_id', 'stage']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_logs');
    }
};
