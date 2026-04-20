<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'attachable_type',
    'attachable_id',
    'file_name',
    'category',
    'file_path',
    'mime_type',
    'size_bytes',
    'uploaded_by',
])]
class Attachment extends Model
{
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function extensionLabel(): string
    {
        return strtoupper(pathinfo($this->file_name, PATHINFO_EXTENSION) ?: 'FILE');
    }

    public function resolvedCategory(): string
    {
        if (filled($this->category)) {
            return (string) $this->category;
        }

        $name = strtolower($this->file_name);

        return match (true) {
            str_contains($name, 'facture') => 'Factures',
            str_contains($name, 'devis') => 'Devis',
            str_contains($name, 'contrat') => 'Contrats',
            str_contains($name, 'recu'), str_contains($name, 'reçu') => 'Reçus',
            default => 'Archives',
        };
    }

    public function humanSize(): string
    {
        $size = (int) ($this->size_bytes ?? 0);

        if ($size <= 0) {
            return '—';
        }

        if ($size >= 1073741824) {
            return number_format($size / 1073741824, 1) . ' GB';
        }

        if ($size >= 1048576) {
            return number_format($size / 1048576, 1) . ' MB';
        }

        if ($size >= 1024) {
            return number_format($size / 1024, 0) . ' KB';
        }

        return $size . ' B';
    }
}
