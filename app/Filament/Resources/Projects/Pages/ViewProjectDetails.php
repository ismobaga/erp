<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewProjectDetails extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.projects.pages.view-project-details';

    public ?string $internalNote = '';
    public ?string $noteDate = null;
    public ?int $noteUserId = null;

    public function getTitle(): string|Htmlable
    {
        return 'Détails du projet';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Retour à la liste')
                ->color('gray')
                ->url(ProjectResource::getUrl('index')),
            EditAction::make()->label('Modifier'),
        ];
    }

    public function saveInternalNote(): void
    {
        $validated = $this->validate([
            'internalNote' => ['required', 'string', 'min:3', 'max:4000'],
            'noteDate' => ['required', 'date'],
            'noteUserId' => ['required', 'integer', 'exists:users,id'],
        ], [], [
            'internalNote' => 'note interne',
            'noteDate' => 'date de la note',
            'noteUserId' => 'auteur',
        ]);

        $creator = auth()->user();

        abort_unless((bool) $creator, 403);

        /** @var Project $project */
        $project = $this->getRecord();

        Note::create([
            'notable_type' => Project::class,
            'notable_id' => $project->getKey(),
            'user_id' => $validated['noteUserId'],
            'created_by' => $creator->getKey(),
            'noted_at' => $validated['noteDate'],
            'body' => $validated['internalNote'],
        ]);

        $this->record = $project->fresh(['client', 'service', 'assignee', 'approver']);
        $this->internalNote = '';
        $this->noteDate = null;
        $this->noteUserId = null;

        Notification::make()
            ->title('Note interne enregistrée.')
            ->success()
            ->send();
    }

    public function getAvailableUsers(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn(User $u): array => ['id' => $u->getKey(), 'name' => $u->name])
            ->all();
    }

    public function getProgressPercentage(): int
    {
        /** @var Project $project */
        $project = $this->getRecord();

        if ($project->status === 'completed') {
            return 100;
        }

        if ($project->status === 'cancelled') {
            return 0;
        }

        if ($project->status === 'planned') {
            return 20;
        }

        if ($project->status === 'on_hold') {
            return 45;
        }

        if ($project->start_date && $project->due_date) {
            $startDate = now()->parse($project->start_date);
            $dueDate = now()->parse($project->due_date);

            if (now()->lt($startDate)) {
                return 5;
            }

            $totalDays = max(1, $startDate->diffInDays($dueDate));
            $currentDate = now()->lt($dueDate) ? now() : $dueDate;
            $elapsedDays = $startDate->diffInDays($currentDate);

            return (int) max(10, min(95, round(($elapsedDays / $totalDays) * 100)));
        }

        return $project->status === 'in_progress' ? 60 : 0;
    }

    public function getStatusLabel(): string
    {
        return match ($this->getRecord()->status) {
            'planned' => 'Planifié',
            'in_progress' => 'En cours',
            'on_hold' => 'En pause',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => 'Non défini',
        };
    }

    public function getStatusClasses(): string
    {
        return match ($this->getRecord()->status) {
            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'in_progress' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
            'on_hold' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'cancelled' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-300',
        };
    }

    public function getApprovalLabel(): string
    {
        return match ($this->getRecord()->approval_status) {
            'approved' => 'Approuvé',
            'review' => 'À vérifier',
            'rejected' => 'Rejeté',
            default => 'En attente',
        };
    }

    public function getApprovalClasses(): string
    {
        return match ($this->getRecord()->approval_status) {
            'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'review' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'rejected' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-300',
        };
    }

    public function getRelatedAttachments(): array
    {
        /** @var Project $project */
        $project = $this->getRecord();

        return $project->attachments()
            ->latest()
            ->take(4)
            ->get()
            ->map(fn(Attachment $attachment): array => [
                'name' => $attachment->file_name,
                'meta' => $attachment->humanSize() . ' • ' . ($attachment->created_at?->translatedFormat('d M Y') ?? '—'),
                'downloadUrl' => route('attachments.download', $attachment),
            ])
            ->all();
    }

    public function getRelatedPayments(): array
    {
        /** @var Project $project */
        $project = $this->getRecord();

        if (!$project->client_id) {
            return [];
        }

        return Payment::query()
            ->with('invoice')
            ->where('client_id', $project->client_id)
            ->latest('payment_date')
            ->take(3)
            ->get()
            ->map(fn(Payment $payment): array => [
                'label' => $payment->invoice?->invoice_number ?: 'Paiement libre',
                'amount' => $this->formatMoney((float) $payment->amount),
                'status' => strtoupper($payment->reconciliationState() === 'completed' ? 'payée' : 'en attente'),
                'statusClasses' => $payment->reconciliationState() === 'completed'
                    ? 'bg-tertiary-fixed/40 text-on-tertiary-fixed-variant'
                    : 'bg-secondary-fixed/50 text-on-secondary-fixed-variant',
                'meta' => $payment->reference ?: 'Sans référence',
            ])
            ->all();
    }

    public function getRelatedInvoices(): array
    {
        /** @var Project $project */
        $project = $this->getRecord();

        if (!$project->client_id) {
            return [];
        }

        return Invoice::query()
            ->where('client_id', $project->client_id)
            ->latest('issue_date')
            ->take(3)
            ->get()
            ->map(fn(Invoice $invoice): array => [
                'number' => $invoice->invoice_number,
                'total' => $this->formatMoney((float) $invoice->total),
                'status' => strtoupper(str_replace('_', ' ', $invoice->status)),
                'meta' => 'Émise le ' . ($invoice->issue_date ? now()->parse((string) $invoice->issue_date)->format('d/m/Y') : '—'),
            ])
            ->all();
    }

    public function getRelatedQuotes(): array
    {
        /** @var Project $project */
        $project = $this->getRecord();

        if (!$project->client_id) {
            return [];
        }

        return Quote::query()
            ->where('client_id', $project->client_id)
            ->latest('issue_date')
            ->take(3)
            ->get()
            ->map(fn(Quote $quote): array => [
                'number' => $quote->quote_number,
                'total' => $this->formatMoney((float) $quote->total),
                'status' => strtoupper($quote->status),
                'meta' => 'Valide jusqu’au ' . ($quote->valid_until ? now()->parse((string) $quote->valid_until)->format('d/m/Y') : '—'),
            ])
            ->all();
    }

    public function getNoteEntries(): array
    {
        /** @var Project $project */
        $project = $this->getRecord();

        $entries = [];

        $entries[] = [
            'title' => 'Création',
            'meta' => 'Enregistré le ' . ($project->created_at?->format('d/m/Y H:i') ?? '—'),
            'content' => 'Le projet a été créé pour le client ' . ($project->client?->company_name ?: $project->client?->contact_name ?: 'non renseigné') . '.',
        ];

        if ($project->start_date) {
            $entries[] = [
                'title' => 'Démarrage prévu',
                'meta' => 'Calendrier opérationnel',
                'content' => 'Date de début planifiée : ' . now()->parse((string) $project->start_date)->format('d/m/Y') . '.',
            ];
        }

        if ($project->due_date) {
            $entries[] = [
                'title' => 'Échéance',
                'meta' => 'Planning de livraison',
                'content' => 'Date cible de livraison : ' . now()->parse((string) $project->due_date)->format('d/m/Y') . '.',
            ];
        }

        $activityEntries = ActivityLog::query()
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->getKey())
            ->whereIn('action', ['project_approved', 'project_started', 'project_completed', 'project_on_hold'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function (ActivityLog $log): array {
                $title = match ($log->action) {
                    'project_approved' => 'Validation',
                    'project_started' => 'Exécution',
                    'project_completed' => 'Clôture',
                    'project_on_hold' => 'Mise en pause',
                    default => 'Activité projet',
                };

                $content = match ($log->action) {
                    'project_approved' => 'Le projet a été approuvé dans le circuit de validation.',
                    'project_started' => 'Le projet est passé en phase d’exécution.',
                    'project_completed' => 'Le projet a été marqué comme terminé.',
                    'project_on_hold' => 'Le projet a été mis en pause pour revue.',
                    default => 'Une mise à jour a été enregistrée.',
                };

                if (filled(data_get($log->meta_json, 'notes'))) {
                    $content .= ' ' . data_get($log->meta_json, 'notes');
                }

                return [
                    'title' => $title,
                    'meta' => $log->created_at?->format('d/m/Y H:i') ?? 'Journal système',
                    'content' => $content,
                ];
            })
            ->all();

        $entries = [...$entries, ...$activityEntries];

        $rawNotes = trim((string) $project->notes);

        if ($rawNotes !== '') {
            $blocks = preg_split('/\n{2,}/', $rawNotes) ?: [];

            foreach ($blocks as $block) {
                $lines = preg_split('/\n/', trim($block)) ?: [];
                $meta = array_shift($lines) ?: 'Note interne';
                $content = trim(implode(PHP_EOL, $lines));

                $entries[] = [
                    'title' => 'Note interne',
                    'meta' => $meta,
                    'content' => $content !== '' ? $content : $block,
                ];
            }
        }

        // Structured notes from the notes table
        $structuredNotes = Note::query()
            ->with('author')
            ->where('notable_type', Project::class)
            ->where('notable_id', $project->getKey())
            ->orderByDesc('noted_at')
            ->orderByDesc('id')
            ->get();

        foreach ($structuredNotes as $note) {
            $entries[] = [
                'title' => 'Note interne',
                'meta' => $note->noted_at->format('d/m/Y') . ' — ' . ($note->author?->name ?? 'Inconnu'),
                'content' => $note->body,
            ];
        }

        if ($project->approval_notes) {
            $entries[] = [
                'title' => 'Validation',
                'meta' => 'Circuit d’approbation',
                'content' => $project->approval_notes,
            ];
        }

        $entries[] = [
            'title' => 'Statut actuel',
            'meta' => 'Mise à jour système',
            'content' => 'Le projet est actuellement « ' . $this->getStatusLabel() . ' » avec une validation « ' . $this->getApprovalLabel() . ' ».',
        ];

        return $entries;
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' FCFA';
    }
}
