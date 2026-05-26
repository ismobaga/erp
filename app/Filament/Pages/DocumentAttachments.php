<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Models\Attachment;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\SecureFileUploadService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
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
        $company = currentCompany();

        abort_unless((bool) $company, 403, 'No current company context.');

        $user = auth()->user();
        abort_unless((bool) $user, 403);

        $maxUploadKb = max(512, (int) config('erp.documents.max_upload_kb', 10240));

        $validated = $this->validate([
            'documentCategory' => ['required', 'string', 'max:255'],
            'upload' => ['required', 'file', 'max:'.$maxUploadKb],
        ], [], [
            'documentCategory' => 'catégorie',
            'upload' => 'document',
        ]);

        $file = $validated['upload'];
        $fileSize = $this->resolveUploadSize($file);
        $quotaBytes = max(1, (int) config('erp.documents.quota_mb', 200)) * 1024 * 1024;
        $projectedUsage = (int) Attachment::query()->where('company_id', $company->id)->sum('size_bytes') + $fileSize;

        if ($projectedUsage > $quotaBytes) {
            throw ValidationException::withMessages([
                'upload' => 'Le quota de stockage sécurisé des documents a été dépassé.',
            ]);
        }

        $attachment = app(SecureFileUploadService::class)->storeFile(
            $file,
            User::class,
            $user->getKey(),
            $user->getKey(),
            $company->id,
            $this->documentCategory,
        );

        app(AuditTrailService::class)->log('document_uploaded', $attachment, [
            'category' => $this->documentCategory,
            'disk' => (string) config('erp.documents.disk', 'local'),
            'path' => $attachment->file_path,
            'size_bytes' => $attachment->size_bytes,
        ], $user->getKey());

        $this->reset('upload');
        $this->documentCategory = 'Factures';

        Notification::make()
            ->title('Document téléversé avec succès.')
            ->success()
            ->send();
    }

    public function deleteDocument(int $documentId): void
    {
        $user = auth()->user();
        abort_unless((bool) $user, 403);

        $attachment = Attachment::query()->findOrFail($documentId);
        $canDelete = ($user->can('documents.delete') ?? false) || ((int) $attachment->uploaded_by === (int) $user->getKey());
        abort_unless($canDelete, 403);

        $attachmentName = $attachment->file_name;

        $attachment->delete();

        app(AuditTrailService::class)->log('document_deleted', $attachment, [
            'name' => $attachmentName,
            'disk' => (string) config('erp.documents.disk', 'local'),
            'path' => $attachment->file_path,
        ], $user->getKey());

        Notification::make()
            ->title('Document supprimé.')
            ->success()
            ->send();
    }

    protected function resolveUploadSize($file): int
    {
        $path = $file->getPathname();

        if (is_string($path) && is_file($path)) {
            $size = @filesize($path);

            if ($size !== false) {
                return (int) $size;
            }
        }

        try {
            return (int) $file->getSize();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'upload' => 'Le fichier temporaire a expiré. Sélectionnez-le à nouveau puis réessayez.',
            ]);
        }
    }

    protected function getViewData(): array
    {
        $company = currentCompany();
        $documents = $this->filteredDocuments();
        $statsDocuments = Attachment::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->select(['file_name', 'category', 'size_bytes'])
            ->latest()
            ->get();
        $totalBytes = (int) Attachment::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->sum('size_bytes');
        $quotaMb = max(1, (int) config('erp.documents.quota_mb', 200));
        $quotaBytes = $quotaMb * 1024 * 1024;

        return [
            'documents' => $documents,
            'storage' => [
                'used' => $this->formatBytes($totalBytes),
                'quota' => $quotaMb.' MB',
                'percent' => min(100, $quotaBytes > 0 ? (int) round(($totalBytes / $quotaBytes) * 100) : 0),
            ],
            'categories' => $this->categoryBreakdown($statsDocuments),
            'exploration' => $this->typeExploration($statsDocuments),
        ];
    }

    protected function filteredDocuments(): Collection
    {
        $query = Attachment::query()
            ->select(['id', 'file_name', 'category', 'size_bytes', 'created_at', 'uploaded_by'])
            ->latest();

        $this->applyDocumentFilters($query);

        return $query
            ->get()
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
                    'canDelete' => (auth()->user()?->can('documents.delete') ?? false)
                        || ((int) $attachment->uploaded_by === (int) auth()->id()),
                    'icon' => $this->iconFor($attachment),
                    'tint' => $this->tintFor($attachment),
                ];
            });
    }

    protected function applyDocumentFilters(Builder $query): void
    {
        if (filled($this->search)) {
            $needle = trim($this->search);

            $query->where(function (Builder $inner) use ($needle): void {
                $inner->where('file_name', 'like', '%'.$needle.'%')
                    ->orWhere('category', 'like', '%'.$needle.'%');
            });
        }

        match ($this->filterType) {
            'pdf' => $query->whereRaw('LOWER(file_name) like ?', ['%.pdf']),
            'excel' => $query->where(function (Builder $inner): void {
                $inner->whereRaw('LOWER(file_name) like ?', ['%.xls'])
                    ->orWhereRaw('LOWER(file_name) like ?', ['%.xlsx'])
                    ->orWhereRaw('LOWER(file_name) like ?', ['%.csv']);
            }),
            'docs' => $query->where(function (Builder $inner): void {
                $inner->whereRaw('LOWER(file_name) like ?', ['%.doc'])
                    ->orWhereRaw('LOWER(file_name) like ?', ['%.docx'])
                    ->orWhereRaw('LOWER(file_name) like ?', ['%.txt']);
            }),
            default => null,
        };
    }

    protected function categoryBreakdown(Collection $documents): array
    {
        $labels = ['Factures', 'Contrats', 'Devis', 'Reçus', 'Archives'];

        return collect($labels)->map(function (string $label) use ($documents): array {
            $count = $documents->filter(fn (Attachment $attachment) => $attachment->resolvedCategory() === $label)->count();

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
            $matching = $documents->filter(fn (Attachment $attachment) => in_array($attachment->extensionLabel(), $config['extensions'], true));

            return [
                'label' => $label,
                'count' => $matching->count(),
                'size' => $this->formatBytes((int) $matching->sum(fn (Attachment $attachment) => (int) ($attachment->size_bytes ?? 0))),
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

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 MB';
        }

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 0).' KB';
    }
}
