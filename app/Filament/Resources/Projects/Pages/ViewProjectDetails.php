<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
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
        ], [], [
            'internalNote' => 'note interne',
        ]);

        $user = auth()->user();

        abort_unless((bool) $user, 403);

        /** @var Project $project */
        $project = $this->getRecord();
        $project->addInternalNote($user, $validated['internalNote']);

        $this->record = $project->fresh(['client', 'service', 'assignee', 'approver']);
        $this->internalNote = '';

        Notification::make()
            ->title('Note interne enregistrée.')
            ->success()
            ->send();
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

    public function getNoteEntries(): array
    {
        /** @var Project $project */
        $project = $this->getRecord();

        $entries = [];
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
}
