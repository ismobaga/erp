<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'attachable_type',
    'attachable_id',
    'file_name',
    'file_path',
    'mime_type',
    'uploaded_by',
])]
class Attachment extends Model
{
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
