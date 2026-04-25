<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('noted_at');
            $table->text('body');
            $table->timestamps();

            $table->index(['notable_type', 'notable_id', 'noted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
