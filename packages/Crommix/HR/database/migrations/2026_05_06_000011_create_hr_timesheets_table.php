<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_timesheets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->decimal('regular_hours', 7, 2)->default(0);
            $table->decimal('overtime_hours', 7, 2)->default(0);
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_timesheets');
    }
};
