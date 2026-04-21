<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description');
            $table->string('frequency');
            $table->string('export_format')->default('pdf');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('selected_modules')->nullable();
            $table->boolean('include_charts')->default(true);
            $table->string('schedule_email')->nullable();
            $table->datetime('next_execution_at');
            $table->datetime('last_executed_at')->nullable();
            $table->string('last_path')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['status', 'next_execution_at']);
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
