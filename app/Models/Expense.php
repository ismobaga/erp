<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
    'recorded_by',
])]
class Expense extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }
}
