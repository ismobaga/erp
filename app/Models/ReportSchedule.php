<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'owner_id',
    'description',
    'frequency',
    'export_format',
    'start_date',
    'end_date',
    'selected_modules',
    'include_charts',
    'schedule_email',
    'next_execution_at',
    'last_executed_at',
    'last_path',
    'status',
])]
class ReportSchedule extends Model
{
    use HasCompanyScope;
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'selected_modules' => 'array',
            'include_charts' => 'boolean',
            'next_execution_at' => 'datetime',
            'last_executed_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->active()->where('next_execution_at', '<=', now());
    }

    public function scopeForOwner(Builder $query, ?int $userId): Builder
    {
        return $query->where('owner_id', $userId);
    }

    public function nextRun(): Carbon
    {
        if ($this->next_execution_at === null) {
            return now();
        }

        return match ($this->frequency) {
            'daily' => $this->next_execution_at->copy()->addDay(),
            'monthly' => $this->next_execution_at->copy()->addMonth(),
            default => $this->next_execution_at->copy()->addWeek(),
        };
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function formatFrequencyLabel(): string
    {
        return match ($this->frequency) {
            'daily' => __('erp.reports.schedule_frequencies.daily'),
            'monthly' => __('erp.reports.schedule_frequencies.monthly'),
            default => __('erp.reports.schedule_frequencies.weekly'),
        };
    }

    public function statusClasses(): string
    {
        return match ($this->status) {
            'active' => 'bg-green-100 text-green-800',
            'paused' => 'bg-amber-100 text-amber-800',
            default => 'bg-sky-100 text-sky-800',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => __('erp.reports.scheduled_statuses.active'),
            'paused' => __('erp.reports.scheduled_statuses.pending'),
            default => __('erp.reports.scheduled_statuses.recent'),
        };
    }

    public function lastGeneratedLabel(): string
    {
        return $this->last_executed_at
            ? $this->last_executed_at->format('d M Y - H:i')
            : __('erp.reports.scheduled_statuses.never');
    }

    public function nextExecutionLabel(): string
    {
        return $this->next_execution_at
            ? $this->next_execution_at->format('d M Y - H:i')
            : __('erp.reports.scheduled_statuses.never');
    }
}
