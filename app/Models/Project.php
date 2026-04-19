<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'service_id',
    'name',
    'description',
    'status',
    'approval_status',
    'approval_notes',
    'approved_by',
    'approved_at',
    'start_date',
    'due_date',
    'assigned_to',
    'notes',
    'created_by',
    'updated_by',
])]
class Project extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

        $this->logActivity('project_approved', $user, $notes);
    }

    public function markInProgress(): void
    {
        $this->forceFill(['status' => 'in_progress'])->save();

        if ($this->approval_status === 'pending') {
            $this->forceFill(['approval_status' => 'approved'])->saveQuietly();
        }

        if ($user = auth()->user()) {
            $this->logActivity('project_started', $user);
        }
    }

    public function markCompleted(): void
    {
        $this->forceFill(['status' => 'completed'])->save();

        if ($user = auth()->user()) {
            $this->logActivity('project_completed', $user);
        }
    }

    public function markOnHold(?User $user = null, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => 'on_hold',
            'approval_notes' => $notes,
        ])->save();

        if ($user) {
            $this->logActivity('project_on_hold', $user, $notes);
        }
    }

    protected function logActivity(string $action, User $user, ?string $notes = null): void
    {
        ActivityLog::create([
            'user_id' => $user->getKey(),
            'action' => $action,
            'subject_type' => self::class,
            'subject_id' => $this->getKey(),
            'meta_json' => [
                'name' => $this->name,
                'status' => $this->status,
                'approval_status' => $this->approval_status,
                'notes' => $notes,
            ],
        ]);
    }
}
