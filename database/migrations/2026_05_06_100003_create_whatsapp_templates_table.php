<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('custom'); // invoice | quote | reminder | dunning | custom
            $table->text('body'); // Template body with {variable} placeholders
            $table->json('variables')->nullable(); // Array of variable names
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
