<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'notable_type',
    'notable_id',
    'user_id',
    'created_by',
    'noted_at',
    'body',
])]
class Note extends Model
{
    protected function casts(): array
    {
        return [
            'noted_at' => 'date',
        ];
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    /** The user attributed as the note author (may differ from creator). */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The user who actually entered this note in the system. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
