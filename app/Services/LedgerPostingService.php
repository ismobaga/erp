<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\FinancialPeriod;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Services\AuditTrailService;
use Illuminate\Support\Facades\DB;

class LedgerPostingService
{
    public function __construct(
        private readonly SequenceService $sequences,
    ) {}

    /**
     * Default account codes used by automated posting rules.
     * These can be overridden via config erp.ledger.accounts.
     */
    private const DEFAULTS = [
        'cash' => '1010',
        'accounts_receivable' => '1100',
        'accounts_payable' => '2100',
        'tax_payable' => '2200',
        'sales_revenue' => '4100',
        'expense_travel' => '5100',
        'expense_supplies' => '5200',
        'expense_operations' => '5300',
        'expense_payroll' => '5400',
        'expense_compliance' => '5500',
        'expense_other' => '5900',
    ];

    // -------------------------------------------------------------------------
    // Invoice posting
    // -------------------------------------------------------------------------

    /**
     * When an invoice is issued (status becomes 'sent'):
     *   DR Accounts Receivable (1100)  → invoice total
     *   CR Sales Revenue (4100)        → invoice subtotal after discount
     *   CR Tax Payable (2200)          → tax portion (if any)
     */
    public function postInvoice(Invoice $invoice, ?int $userId = null): ?JournalEntry
    {
        $total = (float) $invoice->total;

        if ($total <= 0) {
            return null;
        }

        $ar = $this->account('accounts_receivable');
        $revenue = $this->account('sales_revenue');
        $tax = $this->account('tax_payable');

        if (!$ar || !$revenue) {
            return null;
        }

        $taxAmount = (float) $invoice->tax_total;
        $revenueAmount = $total - $taxAmount;

        $lines = [
            [
                'account_id' => $ar->id,
                'debit' => $total,
                'credit' => 0,
                'description' => 'Receivable: ' . $invoice->invoice_number
            ],
            [
                'account_id' => $revenue->id,
                'debit' => 0,
                'credit' => $revenueAmount,
                'description' => 'Revenue: ' . $invoice->invoice_number
            ],
        ];

        if ($taxAmount > 0 && $tax) {
            $lines[] = [
                'account_id' => $tax->id,
                'debit' => 0,
                'credit' => $taxAmount,
                'description' => 'Tax: ' . $invoice->invoice_number
            ];
        }

        return $this->createAndPost(
            date: $invoice->issue_date?->toDateString() ?? now()->toDateString(),
            description: 'Invoice ' . $invoice->invoice_number,
            sourceType: 'invoice',
            sourceId: $invoice->id,
            lines: $lines,
            userId: $userId,
            financialPeriodId: $this->resolvePeriodId($invoice->issue_date),
        );
    }

    // -------------------------------------------------------------------------
    // Payment posting
    // -------------------------------------------------------------------------

    /**
     * When a payment is recorded:
     *   DR Cash/Bank (1010)            → payment amount
     *   CR Accounts Receivable (1100)  → payment amount
     */
    public function postPayment(Payment $payment, ?int $userId = null): ?JournalEntry
    {
        $amount = (float) $payment->amount;

        if ($amount <= 0) {
            return null;
        }

        $cash = $this->account('cash');
        $ar = $this->account('accounts_receivable');

        if (!$cash || !$ar) {
            return null;
        }

        $reference = $payment->reference ?: ('PAY-' . $payment->id);

        return $this->createAndPost(
            date: $payment->payment_date?->toDateString() ?? now()->toDateString(),
            description: 'Payment ' . $reference,
            sourceType: 'payment',
            sourceId: $payment->id,
            lines: [
                [
                    'account_id' => $cash->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Cash: ' . $reference
                ],
                [
                    'account_id' => $ar->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Receivable: ' . $reference
                ],
            ],
            userId: $userId,
            financialPeriodId: $this->resolvePeriodId($payment->payment_date),
        );
    }

    // -------------------------------------------------------------------------
    // Expense posting
    // -------------------------------------------------------------------------

    /**
     * When an expense is approved:
     *   DR Expense account (category-driven 5xxx)
     *   CR Accounts Payable (2100)
     */
    public function postExpense(Expense $expense, ?int $userId = null): ?JournalEntry
    {
        $amount = (float) $expense->amount;

        if ($amount <= 0) {
            return null;
        }

        $expenseAccount = $this->account($this->expenseAccountKey((string) $expense->category));
        $ap = $this->account('accounts_payable');

        if (!$expenseAccount || !$ap) {
            return null;
        }

        $reference = $expense->reference ?: ('EXP-' . $expense->id);

        return $this->createAndPost(
            date: $expense->expense_date?->toDateString() ?? now()->toDateString(),
            description: 'Expense ' . $reference . ': ' . $expense->title,
            sourceType: 'expense',
            sourceId: $expense->id,
            lines: [
                [
                    'account_id' => $expenseAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => $expense->title
                ],
                [
                    'account_id' => $ap->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Payable: ' . $reference
                ],
            ],
            userId: $userId,
            financialPeriodId: $this->resolvePeriodId($expense->expense_date),
        );
    }

    // -------------------------------------------------------------------------
    // Credit note posting
    // -------------------------------------------------------------------------

    /**
     * When a credit note is issued/approved:
     *   DR Sales Revenue (4100)       → credit amount (revenue reversal)
     *   CR Accounts Receivable (1100) → credit amount
     */
    public function postCreditNote(CreditNote $creditNote, ?int $userId = null): ?JournalEntry
    {
        $amount = (float) $creditNote->amount;

        if ($amount <= 0) {
            return null;
        }

        $revenue = $this->account('sales_revenue');
        $ar = $this->account('accounts_receivable');

        if (!$revenue || !$ar) {
            return null;
        }

        return $this->createAndPost(
            date: $creditNote->issue_date?->toDateString() ?? now()->toDateString(),
            description: 'Credit note ' . $creditNote->credit_number,
            sourceType: 'credit_note',
            sourceId: $creditNote->id,
            lines: [
                [
                    'account_id' => $revenue->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Revenue reversal: ' . $creditNote->credit_number
                ],
                [
                    'account_id' => $ar->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Receivable credit: ' . $creditNote->credit_number
                ],
            ],
            userId: $userId,
            financialPeriodId: $this->resolvePeriodId($creditNote->issue_date),
        );
    }

    // -------------------------------------------------------------------------
    // Reversal entries
    // -------------------------------------------------------------------------

    /**
     * Create a reversal journal entry for a posted entry:
     * - All debit/credit amounts on the lines are swapped.
     * - The reversal is immediately posted.
     * - The original entry is NOT voided; both remain in the ledger.
     */
    public function reverse(JournalEntry $entry, ?int $userId = null, ?string $reason = null): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => __('erp.ledger.reverse_only_posted'),
            ]);
        }

        return DB::transaction(function () use ($entry, $userId, $reason): JournalEntry {
            // Load lines inside the transaction for data consistency.
            $entry->loadMissing('lines');
            $description = __('erp.ledger.reversal_of') . ': ' . $entry->entry_number;

            if ($reason) {
                $description .= ' — ' . $reason;
            }

            $reversal = JournalEntry::create([
                'entry_number'       => $this->generateEntryNumber(now()->toDateString()),
                'entry_date'         => now()->toDateString(),
                'description'        => $description,
                'status'             => 'draft',
                'source_type'        => null,
                'source_id'          => null,
                'reversal_of'        => $entry->id,
                'financial_period_id' => $this->resolvePeriodId(now()),
                'created_by'         => $userId,
            ]);

            foreach ($entry->lines as $line) {
                $reversal->lines()->create([
                    'account_id'  => $line->account_id,
                    'debit'       => $line->credit,
                    'credit'      => $line->debit,
                    'description' => $line->description,
                ]);
            }

            $reversal->load('lines');
            $reversal->post($userId);

            app(AuditTrailService::class)->log('journal_entry_reversed', $entry, [
                'reversal_entry_number' => $reversal->entry_number,
                'reason' => $reason,
            ], $userId);

            return $reversal;
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAndPost(
        string $date,
        string $description,
        string $sourceType,
        int $sourceId,
        array $lines,
        ?int $userId,
        ?int $financialPeriodId,
    ): JournalEntry {
        return DB::transaction(function () use ($date, $description, $sourceType, $sourceId, $lines, $userId, $financialPeriodId): JournalEntry {
            $existing = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->loadMissing('lines');

                if ($existing->status === 'draft' && $existing->lines->isNotEmpty()) {
                    $existing->post($userId);
                }

                return $existing->fresh(['lines']);
            }

            $entry = JournalEntry::create([
                'entry_number' => $this->generateEntryNumber($date),
                'entry_date' => $date,
                'description' => $description,
                'status' => 'draft',
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'financial_period_id' => $financialPeriodId,
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            $entry->load('lines');
            $entry->post($userId);

            return $entry;
        });
    }

    private function account(string $key): ?LedgerAccount
    {
        $code = (string) config('erp.ledger.accounts.' . $key, self::DEFAULTS[$key] ?? '');

        if (blank($code)) {
            return null;
        }

        return LedgerAccount::findByCode($code);
    }

    private function generateEntryNumber(string $date): string
    {
        $year   = substr($date, 0, 4);
        $prefix = 'JE-' . $year . '-';

        $seq = $this->sequences->next('journal_entry', $year);

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    private function resolvePeriodId(mixed $date): ?int
    {
        if (blank($date)) {
            return null;
        }

        return FinancialPeriod::query()
            ->current($date)
            ->value('id');
    }

    private function expenseAccountKey(string $category): string
    {
        return match ($category) {
            'travel' => 'expense_travel',
            'supplies' => 'expense_supplies',
            'operations' => 'expense_operations',
            'payroll' => 'expense_payroll',
            'compliance' => 'expense_compliance',
            default => 'expense_other',
        };
    }
}
