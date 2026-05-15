<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
class Attachment extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected static function booted(): void
    {
        static::saving(function (self $attachment): void {
            $attachableType = $attachment->attachable_type;
            $attachable = null;

            if (is_string($attachableType) && class_exists($attachableType) && method_exists($attachableType, 'withoutCompanyScope')) {
                $attachable = $attachableType::withoutCompanyScope()->find($attachment->attachable_id);
            } else {
                $attachable = $attachment->attachable;
            }

            if (! $attachable || ! isset($attachable->company_id)) {
                return;
            }

            if (blank($attachment->company_id)) {
                $attachment->company_id = (int) $attachable->company_id;
            }

            if ((int) $attachable->company_id !== (int) $attachment->company_id) {
                throw ValidationException::withMessages([
                    'attachable_id' => 'The related record belongs to another company.',
                ]);
            }

            if (app()->bound('currentCompany') && (int) app('currentCompany')->id !== (int) $attachment->company_id) {
                throw ValidationException::withMessages([
                    'company_id' => 'The related record belongs to another company context.',
                ]);
            }
        });

        static::deleting(function (self $attachment): void {
            $disk = (string) config('erp.documents.disk', 'local');
            $path = ltrim((string) $attachment->file_path, '/');

            if ($path !== '' && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        });
    }

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
            return number_format($size / 1073741824, 1).' GB';
        }

        if ($size >= 1048576) {
            return number_format($size / 1048576, 1).' MB';
        }

        if ($size >= 1024) {
            return number_format($size / 1024, 0).' KB';
        }

        return $size.' B';
    }
}
