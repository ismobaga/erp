<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('portal_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('open'); // open, replied, closed
            $table->string('priority')->default('normal'); // normal, urgent
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id', 'status'], 'portal_tickets_company_client_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_tickets');
    }
};
