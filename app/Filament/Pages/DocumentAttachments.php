<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Models\Attachment;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class DocumentAttachments extends Page
{
    use HasPermissionAccess;
    use WithFileUploads;

    protected static string $permissionScope = 'documents';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $title = 'Gestion des Documents';

    protected static ?string $slug = 'documents';

    protected string $view = 'filament.pages.document-attachments';

    public string $search = '';

    public string $filterType = 'all';

    public string $documentCategory = 'Factures';

    public $upload = null;

    public function setFilterType(string $type): void
    {
        $this->filterType = in_array($type, ['all', 'pdf', 'excel', 'docs'], true) ? $type : 'all';
    }

    public function uploadDocument(): void
    {
        $disk = (string) config('erp.documents.disk', 'local');
        $directory = trim((string) config('erp.documents.directory', 'attachments'), '/');
        $maxUploadKb = max(512, (int) config('erp.documents.max_upload_kb', 10240));
        $allowedExtensions = (array) config('erp.documents.allowed_extensions', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'zip', 'txt']);

        $validated = $this->validate([
            'documentCategory' => ['required', 'string', 'max:255'],
            'upload' => ['required', 'file', 'max:' . $maxUploadKb, 'mimes:' . implode(',', $allowedExtensions)],
        ], [], [
            'documentCategory' => 'catégorie',
            'upload' => 'document',
        ]);

        $user = auth()->user();

        abort_unless((bool) $user, 403);

        $file = $validated['upload'];
        $quotaBytes = max(1, (int) config('erp.documents.quota_mb', 200)) * 1024 * 1024;
        $projectedUsage = (int) Attachment::query()->sum('size_bytes') + (int) $file->getSize();

        if ($projectedUsage > $quotaBytes) {
            throw ValidationException::withMessages([
                'upload' => 'Le quota de stockage sécurisé des documents a été dépassé.',
            ]);
        }

        $storedPath = $file->storeAs(
            $directory . '/' . now()->format('Y/m'),
            Str::uuid() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . Str::lower($file->getClientOriginalExtension()),
            $disk
        );

        $attachment = Attachment::create([
            'attachable_type' => User::class,
            'attachable_id' => $user->getKey(),
            'file_name' => $file->getClientOriginalName(),
            'category' => $this->documentCategory,
            'file_path' => $storedPath,
            'mime_type' => $this->resolveMimeType($file->getClientOriginalExtension(), (string) ($file->getClientMimeType() ?: $file->getMimeType())),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $user->getKey(),
        ]);

        app(\App\Services\AuditTrailService::class)->log('document_uploaded', $attachment, [
            'category' => $this->documentCategory,
            'disk' => $disk,
            'path' => $storedPath,
            'size_bytes' => $file->getSize(),
        ], $user->getKey());

        $this->reset('upload');
        $this->documentCategory = 'Factures';

        Notification::make()
            ->title('Document téléversé avec succès.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $documents = $this->filteredDocuments();
        $allDocuments = Attachment::query()->latest()->get();
        $totalBytes = (int) $allDocuments->sum(fn(Attachment $attachment) => (int) ($attachment->size_bytes ?? 0));
        $quotaMb = max(1, (int) config('erp.documents.quota_mb', 200));
        $quotaBytes = $quotaMb * 1024 * 1024;

        return [
            'documents' => $documents,
            'storage' => [
                'used' => $this->formatBytes($totalBytes),
                'quota' => $quotaMb . ' MB',
                'percent' => min(100, $quotaBytes > 0 ? (int) round(($totalBytes / $quotaBytes) * 100) : 0),
            ],
            'categories' => $this->categoryBreakdown($allDocuments),
            'exploration' => $this->typeExploration($allDocuments),
        ];
    }

    protected function filteredDocuments(): Collection
    {
        return Attachment::query()
            ->latest()
            ->get()
            ->filter(function (Attachment $attachment): bool {
                $matchesSearch = blank($this->search)
                    || str_contains(Str::lower($attachment->file_name), Str::lower($this->search))
                    || str_contains(Str::lower($attachment->resolvedCategory()), Str::lower($this->search));

                if (!$matchesSearch) {
                    return false;
                }

                return match ($this->filterType) {
                    'pdf' => $attachment->extensionLabel() === 'PDF',
                    'excel' => in_array($attachment->extensionLabel(), ['XLS', 'XLSX', 'CSV'], true),
                    'docs' => in_array($attachment->extensionLabel(), ['DOC', 'DOCX', 'TXT'], true),
                    default => true,
                };
            })
            ->values()
            ->map(function (Attachment $attachment): array {
                return [
                    'id' => $attachment->getKey(),
                    'name' => $attachment->file_name,
                    'category' => $attachment->resolvedCategory(),
                    'type' => $attachment->extensionLabel(),
                    'date' => $attachment->created_at?->translatedFormat('d M Y') ?? '—',
                    'size' => $attachment->humanSize(),
                    'downloadUrl' => URL::temporarySignedRoute(
                        'attachments.download',
                        now()->addMinutes((int) config('erp.documents.download_url_ttl_minutes', 30)),
                        ['attachment' => $attachment]
                    ),
                    'icon' => $this->iconFor($attachment),
                    'tint' => $this->tintFor($attachment),
                ];
            });
    }

    protected function categoryBreakdown(Collection $documents): array
    {
        $labels = ['Factures', 'Contrats', 'Devis', 'Reçus', 'Archives'];

        return collect($labels)->map(function (string $label) use ($documents): array {
            $count = $documents->filter(fn(Attachment $attachment) => $attachment->resolvedCategory() === $label)->count();

            return [
                'label' => $label,
                'count' => $count,
            ];
        })->all();
    }

    protected function typeExploration(Collection $documents): array
    {
        $groups = [
            'Documents PDF' => ['extensions' => ['PDF'], 'tone' => 'text-red-600 bg-red-50'],
            'Documents bureautiques' => ['extensions' => ['DOC', 'DOCX', 'TXT'], 'tone' => 'text-sky-600 bg-sky-50'],
            'Tableaux de bord' => ['extensions' => ['XLS', 'XLSX', 'CSV'], 'tone' => 'text-emerald-600 bg-emerald-50'],
            'Archives ZIP' => ['extensions' => ['ZIP'], 'tone' => 'text-amber-600 bg-amber-50'],
        ];

        return collect($groups)->map(function (array $config, string $label) use ($documents): array {
            $matching = $documents->filter(fn(Attachment $attachment) => in_array($attachment->extensionLabel(), $config['extensions'], true));

            return [
                'label' => $label,
                'count' => $matching->count(),
                'size' => $this->formatBytes((int) $matching->sum(fn(Attachment $attachment) => (int) ($attachment->size_bytes ?? 0))),
                'tone' => $config['tone'],
            ];
        })->values()->all();
    }

    protected function iconFor(Attachment $attachment): string
    {
        return match ($attachment->extensionLabel()) {
            'PDF' => 'PDF',
            'XLS', 'XLSX', 'CSV' => 'XLS',
            'DOC', 'DOCX', 'TXT' => 'DOC',
            'ZIP' => 'ZIP',
            default => 'FILE',
        };
    }

    protected function tintFor(Attachment $attachment): string
    {
        return match ($attachment->extensionLabel()) {
            'PDF' => 'bg-red-50 text-red-600',
            'XLS', 'XLSX', 'CSV' => 'bg-emerald-50 text-emerald-600',
            'DOC', 'DOCX', 'TXT' => 'bg-sky-50 text-sky-600',
            'ZIP' => 'bg-amber-50 text-amber-600',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    protected function resolveMimeType(string $extension, string $fallback): string
    {
        return match (Str::lower($extension)) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => $fallback !== '' ? $fallback : 'application/octet-stream',
        };
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 MB';
        }

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return number_format($bytes / 1024, 0) . ' KB';
    }
}
