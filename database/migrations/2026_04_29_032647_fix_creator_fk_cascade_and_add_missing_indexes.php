<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hardening migration – addresses two categories of issues found in the
 * architecture review:
 *
 *  1. CASCADE-ON-DELETE on "creator" foreign keys
 *     Deleting a user who created quotes, invoices, expenses, projects or
 *     recorded payments must NOT silently cascade-delete those financial
 *     records.  Audit trails and accounting history must be preserved.
 *     Changed to nullOnDelete() so the FK becomes NULL when the user is
 *     removed – the document remains intact and the "recorded_by"/"created_by"
 *     field can be used to detect "deleted-user" records when needed.
 *
 *     Affected columns:
 *       • quotes.created_by
 *       • invoices.created_by
 *       • payments.recorded_by
 *       • expenses.recorded_by
 *       • projects.created_by
 *
 *  2. Missing indexes
 *     Adds indexes that are exercised by common query paths but were absent
 *     from the initial migrations:
 *       • invoice_items.invoice_id  – recalculateTotals() aggregates by this
 *       • quote_items.quote_id      – recalculateTotals() on quotes
 *       • expenses.approval_status  – approval workflow filters by this
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. quotes.created_by: cascade → null ─────────────────────────────
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // ── 2. invoices.created_by: cascade → null ───────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // ── 3. payments.recorded_by: cascade → null ──────────────────────────
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // ── 4. expenses.recorded_by: cascade → null ──────────────────────────
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // ── 5. projects.created_by: cascade → null ───────────────────────────
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // ── 6. Missing indexes ────────────────────────────────────────────────
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('invoice_id', 'invoice_items_invoice_id_index');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->index('quote_id', 'quote_items_quote_id_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('approval_status', 'expenses_approval_status_index');
        });
    }

    public function down(): void
    {
        // ── Remove added indexes ──────────────────────────────────────────────
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_approval_status_index');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropIndex('quote_items_quote_id_index');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('invoice_items_invoice_id_index');
        });

        // ── Restore cascade deletes on creator FKs ───────────────────────────
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->foreign('recorded_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->foreign('recorded_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
