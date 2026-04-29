<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'company_name',
    'email',
    'intent',
    'message',
    'status',
    'source',
])]
class ContactRequest extends Model
{
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function markAsRead(): void
    {
        $this->update(['status' => 'read']);
    }
}
