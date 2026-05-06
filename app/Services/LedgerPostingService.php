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
use App\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class LedgerPostingService
{
    /** @var array<string, LedgerAccount|null> Per-request account lookup cache keyed by company + account key. */
    private array $accountCache = [];

    public function __construct(
        private readonly SequenceService $sequences,
    ) {
    }

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

        $companyId = $this->resolveCompanyId($invoice->company_id ?? null);

        $ar = $this->account('accounts_receivable', $companyId);
        $revenue = $this->account('sales_revenue', $companyId);
        $tax = $this->account('tax_payable', $companyId);

        if (!$ar || !$revenue) {
            return null;
        }

        $taxAmount = (float) $invoice->tax_total;
        $revenueAmount = (float) Money::of((string) $invoice->total)
            ->subtract(Money::of((string) $invoice->tax_total))
            ->toString();

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
            date: $this->normalizeDateToString($invoice->issue_date),
            description: 'Invoice ' . $invoice->invoice_number,
            sourceType: 'invoice',
            sourceId: $invoice->id,
            lines: $lines,
            userId: $userId,
            financialPeriodId: $this->resolvePeriodId($invoice->issue_date),
            companyId: $companyId,
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

        $companyId = $this->resolveCompanyId($payment->company_id ?? null);

        $cash = $this->account('cash', $companyId);
        $ar = $this->account('accounts_receivable', $companyId);

        if (!$cash || !$ar) {
            return null;
        }

        $reference = $payment->reference ?: ('PAY-' . $payment->id);

        return $this->createAndPost(
            date: $this->normalizeDateToString($payment->payment_date),
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
            companyId: $companyId,
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

        $companyId = $this->resolveCompanyId($expense->company_id ?? null);

        $expenseAccount = $this->account($this->expenseAccountKey((string) $expense->category), $companyId);
        $ap = $this->account('accounts_payable', $companyId);

        if (!$expenseAccount || !$ap) {
            return null;
        }

        $reference = $expense->reference ?: ('EXP-' . $expense->id);

        return $this->createAndPost(
            date: $this->normalizeDateToString($expense->expense_date),
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
            companyId: $companyId,
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

        $companyId = $this->resolveCompanyId($creditNote->company_id ?? null);

        $revenue = $this->account('sales_revenue', $companyId);
        $ar = $this->account('accounts_receivable', $companyId);

        if (!$revenue || !$ar) {
            return null;
        }

        return $this->createAndPost(
            date: $this->normalizeDateToString($creditNote->issue_date),
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
            companyId: $companyId,
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
        return DB::transaction(function () use ($entry, $userId, $reason): JournalEntry {
            /** @var JournalEntry|null $freshEntry */
            $freshEntry = JournalEntry::query()
                ->whereKey($entry->getKey())
                ->lockForUpdate()
                ->first();

            if ($freshEntry === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'entry' => __('erp.common.not_found'),
                ]);
            }

            if ($freshEntry->status !== 'posted') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => __('erp.ledger.reverse_only_posted'),
                ]);
            }

            $existingReversal = JournalEntry::query()
                ->where('reversal_of', $freshEntry->id)
                ->lockForUpdate()
                ->first();

            if ($existingReversal !== null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reversal' => __('erp.ledger.already_reversed'),
                ]);
            }

            // Load lines inside the transaction for data consistency.
            $freshEntry->loadMissing('lines');
            $description = __('erp.ledger.reversal_of') . ': ' . $freshEntry->entry_number;

            $companyId = $this->resolveCompanyId($freshEntry->company_id ?? null);

            if ($reason) {
                $description .= ' — ' . $reason;
            }

            $reversal = JournalEntry::create([
                'entry_number' => $this->generateEntryNumber(now()->toDateString(), $companyId),
                'entry_date' => now()->toDateString(),
                'description' => $description,
                'status' => 'draft',
                'source_type' => null,
                'source_id' => null,
                'reversal_of' => $freshEntry->id,
                'financial_period_id' => $this->resolvePeriodId(now()),
                'created_by' => $userId,
            ]);

            foreach ($freshEntry->lines as $line) {
                $reversal->lines()->create([
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => $line->description,
                ]);
            }

            $reversal->load('lines');
            $reversal->post($userId);

            app(AuditTrailService::class)->log('journal_entry_reversed', $freshEntry, [
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
        ?int $companyId = null,
    ): JournalEntry {
        return DB::transaction(function () use ($date, $description, $sourceType, $sourceId, $lines, $userId, $financialPeriodId, $companyId): JournalEntry {
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
                'entry_number' => $this->generateEntryNumber($date, $companyId),
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

    private function account(string $key, ?int $companyId = null): ?LedgerAccount
    {
        if ($companyId === null) {
            return null;
        }

        $cacheKey = $companyId . ':' . $key;

        if (array_key_exists($cacheKey, $this->accountCache)) {
            return $this->accountCache[$cacheKey];
        }

        $code = (string) config('erp.ledger.accounts.' . $key, self::DEFAULTS[$key] ?? '');

        if (blank($code)) {
            return $this->accountCache[$cacheKey] = null;
        }

        return $this->accountCache[$cacheKey] = LedgerAccount::findByCode($code, $companyId);
    }

    private function resolveCompanyId(?int $companyId = null): ?int
    {
        if ($companyId !== null) {
            return $companyId;
        }

        if (app()->bound('currentCompany')) {
            return (int) app('currentCompany')->id;
        }

        return null;
    }

    private function generateEntryNumber(string $date, ?int $companyId = null): string
    {
        $year = substr($date, 0, 4);
        $prefix = 'JE-' . $year . '-';

        // Resolve company_id from currentCompany binding if not provided
        if (!$companyId && app()->bound('currentCompany')) {
            $companyId = app('currentCompany')->id;
        }

        $seq = $this->sequences->next('journal_entry', $year, $companyId);

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

    private function normalizeDateToString(mixed $date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date->toDateString();
        }

        if (is_string($date) && $date !== '') {
            return substr($date, 0, 10);
        }

        return now()->toDateString();
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
