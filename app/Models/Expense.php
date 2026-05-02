<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'category',
    'title',
    'description',
    'amount',
    'expense_date',
    'payment_method',
    'vendor',
    'reference',
    'attachment_path',
    'approval_status',
    'approval_notes',
    'approved_by',
    'approved_at',
    'recorded_by',
])]
class Expense extends Model
{
    use HasCompanyScope;
    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Expense $expense): void {
            if ((float) $expense->amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Expense amount must be positive.',
                ]);
            }

            $expense->category = static::normalizeCategory($expense->category);

            $allowedCategories = ['travel', 'supplies', 'operations', 'payroll', 'compliance', 'other'];
            if (!in_array((string) $expense->category, $allowedCategories, true)) {
                throw ValidationException::withMessages([
                    'category' => 'Invalid expense category. Allowed: ' . implode(', ', $allowedCategories) . '.',
                ]);
            }

            FinancialPeriod::ensureDateIsOpen($expense->expense_date, 'expense');
        });

        static::deleting(function (Expense $expense): void {
            FinancialPeriod::ensureDateIsOpen($expense->expense_date, 'expense');
        });
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve(User $user, ?string $notes = null): void
    {
        $this->forceFill([
            'approval_status' => 'approved',
            'approval_notes' => $notes,
            'approved_by' => $user->getKey(),
            'approved_at' => now(),
        ])->save();

        $this->logActivity('expense_approved', $user, $notes);
    }

    public function reject(User $user, ?string $notes = null): void
    {
        $this->forceFill([
            'approval_status' => 'rejected',
            'approval_notes' => $notes,
        ])->save();

        $this->logActivity('expense_rejected', $user, $notes);
    }

    public function markForReview(User $user, ?string $notes = null): void
    {
        $this->forceFill([
            'approval_status' => 'review',
            'approval_notes' => $notes,
        ])->save();

        $this->logActivity('expense_review_requested', $user, $notes);
    }

    protected function logActivity(string $action, User $user, ?string $notes = null): void
    {
        app(AuditTrailService::class)->log(
            $action,
            $this,
            [
                'title' => $this->title,
                'approval_status' => $this->approval_status,
                'notes' => $notes,
            ],
            $user->getKey(),
        );
    }

    protected static function normalizeCategory(mixed $category): string
    {
        $normalized = Str::of((string) $category)
            ->lower()
            ->trim()
            ->replace(['-', ' '], '_')
            ->value();

        return match ($normalized) {
            'travel', 'travels' => 'travel',
            'supplies', 'supply' => 'supplies',
            'operations', 'operation', 'marketing' => 'operations',
            'payroll', 'salary', 'salaries' => 'payroll',
            'compliance', 'audit' => 'compliance',
            'other', 'misc', 'miscellaneous' => 'other',
            default => $normalized,
        };
    }
}
