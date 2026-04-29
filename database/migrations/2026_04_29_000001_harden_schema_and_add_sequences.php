<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema hardening migration – addresses the following issues from the
 * architecture review:
 *
 *  C3  – quote_number already carries a UNIQUE constraint from the initial
 *         migration; this migration supersedes the incorrect review finding.
 *         The race condition in numbering is fixed by SequenceService (C2).
 *
 *  C6  – Change payments.invoice_id foreign key from cascadeOnDelete to
 *         nullOnDelete.  Hard-deleting an invoice must NOT silently destroy
 *         all associated payment records; instead the payment becomes
 *         "unmatched" so financial history is preserved.
 *
 *  C9  – Add missing indexes:
 *          • invoices.client_id   (scoped invoice lookups, totalBalance())
 *          • payments.invoice_id  (refreshFinancials() aggregate)
 *          • payments.payment_date (period-based reporting queries)
 *          • payments.client_id   (client-scoped payment queries)
 *          • quotes.client_id     (client-scoped quote lookups)
 *
 *  C1/C2/C10 – Create the sequences table used by SequenceService.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── Sequences table ──────────────────────────────────────────────────
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('period');
            $table->unsignedBigInteger('next_val')->default(1);
            $table->timestamps();

            $table->unique(['key', 'period']);
        });

        // ── C6: change payments.invoice_id to nullOnDelete ──────────────────
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();
        });

        // ── C9: add missing indexes ──────────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('client_id', 'invoices_client_id_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('invoice_id', 'payments_invoice_id_index');
            $table->index('payment_date', 'payments_payment_date_index');
            $table->index('client_id', 'payments_client_id_index');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->index('client_id', 'quotes_client_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('quotes_client_id_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_client_id_index');
            $table->dropIndex('payments_payment_date_index');
            $table->dropIndex('payments_invoice_id_index');

            // Restore original cascadeOnDelete FK
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->cascadeOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_client_id_index');
        });

        Schema::dropIfExists('sequences');
    }
};
