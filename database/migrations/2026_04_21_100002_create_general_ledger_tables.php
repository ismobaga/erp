<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type'); // asset | liability | equity | revenue | expense
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('normal_balance'); // debit | credit
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('parent_id');

            $table->unique(['company_id', 'code'], 'ledger_accounts_company_id_code_unique');

        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reversal_of')
                ->nullable()
                ->after('void_reason')
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->text('description');
            $table->string('status')->default('draft'); // draft | posted | voided
            $table->string('source_type')->nullable(); // invoice | payment | expense | credit_note | manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('financial_period_id')->nullable()->constrained('financial_periods')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'entry_date']);
            $table->index(['source_type', 'source_id']);
            $table->index(['company_id', 'status', 'entry_date'], 'journal_entries_company_status_date_index');

        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};
