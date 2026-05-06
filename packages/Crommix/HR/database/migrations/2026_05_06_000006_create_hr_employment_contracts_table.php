<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employment_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('contract_type')->default('permanent'); // permanent, fixed_term, probation
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('pay_frequency')->default('monthly'); // monthly, bi_weekly, weekly
            $table->string('status')->default('active'); // draft, active, expired, terminated
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employment_contracts');
    }
};
