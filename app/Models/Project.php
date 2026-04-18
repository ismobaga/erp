<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'client_id',
    'service_id',
    'name',
    'description',
    'status',
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
        ];
    }
}
