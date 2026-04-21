<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LedgerOverview extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.ledger-overview';

    protected function getViewData(): array
    {
        try {
            return [
                'entries' => $this->getEntries(),
                'milestones' => $this->getMilestones(),
                'health' => $this->getHealth(),
                'board' => $this->getProjectBoard(),
                'boardSummary' => $this->getBoardSummary(),
                'actions' => $this->getCriticalActions(),
                'team' => $this->getTeam(),
            ];
        } catch (Throwable) {
            return [
                'entries' => $this->placeholderEntries(),
                'milestones' => $this->placeholderMilestones(),
                'health' => $this->placeholderHealth(),
                'board' => $this->placeholderBoard(),
                'boardSummary' => $this->placeholderBoardSummary(),
                'actions' => $this->placeholderActions(),
                'team' => $this->placeholderTeam(),
            ];
        }
    }

    protected function getEntries(): array
    {
        if (!$this->hasTables(['invoices', 'quotes', 'payments', 'expenses'])) {
            return $this->placeholderEntries();
        }

        $entries = collect()
            ->merge(
                Invoice::query()
                    ->with('client')
                    ->latest()
                    ->take(4)
                    ->get()
                    ->map(fn(Invoice $invoice): array => [
                        'reference' => $invoice->invoice_number,
                        'entity' => $invoice->client?->company_name ?: $invoice->client?->contact_name ?: __('erp.common.account_client'),
                        'meta' => optional($invoice->issue_date)->format('M d, Y') ?? 'Invoice issued',
                        'category' => __('erp.common.invoice'),
                        'category_bg' => '#d6e3ff',
                        'category_fg' => '#2d476f',
                        'value' => $this->money((float) $invoice->total),
                        'status' => str($invoice->status)->replace('_', ' ')->title()->toString(),
                        'status_bg' => $this->statusBg($invoice->status),
                        'status_fg' => $this->statusFg($invoice->status),
                        'status_dot' => $this->statusDot($invoice->status),
                        'pillar' => $this->pillar($invoice->status),
                        'timestamp' => $invoice->created_at,
                    ])
            )
            ->merge(
                Quote::query()
                    ->with('client')
                    ->latest()
                    ->take(3)
                    ->get()
                    ->map(fn(Quote $quote): array => [
                        'reference' => $quote->quote_number,
                        'entity' => $quote->client?->company_name ?: $quote->client?->contact_name ?: __('erp.common.account_client'),
                        'meta' => optional($quote->issue_date)->format('M d, Y') ?? 'Proposal prepared',
                        'category' => __('erp.common.quote'),
                        'category_bg' => '#eff4ff',
                        'category_fg' => '#002045',
                        'value' => $this->money((float) $quote->total),
                        'status' => str($quote->status)->replace('_', ' ')->title()->toString(),
                        'status_bg' => '#eef0f4',
                        'status_fg' => '#43474e',
                        'status_dot' => '#74777f',
                        'pillar' => '#adc7f7',
                        'timestamp' => $quote->created_at,
                    ])
            )
            ->merge(
                Payment::query()
                    ->with(['client', 'invoice'])
                    ->latest()
                    ->take(3)
                    ->get()
                    ->map(fn(Payment $payment): array => [
                        'reference' => $payment->reference ?: ('PAY-' . $payment->getKey()),
                        'entity' => $payment->client?->company_name ?: $payment->client?->contact_name ?: __('erp.common.account_client'),
                        'meta' => optional($payment->payment_date)->format('M d, Y') ?? 'Payment recorded',
                        'category' => __('erp.common.payment'),
                        'category_bg' => '#d5e3fc',
                        'category_fg' => '#3a485b',
                        'value' => $this->money((float) $payment->amount),
                        'status' => __('erp.common.recorded'),
                        'status_bg' => '#dff8f0',
                        'status_fg' => '#005048',
                        'status_dot' => '#43af9f',
                        'pillar' => '#8df5e4',
                        'timestamp' => $payment->created_at,
                    ])
            )
            ->merge(
                Expense::query()
                    ->latest()
                    ->take(2)
                    ->get()
                    ->map(fn(Expense $expense): array => [
                        'reference' => $expense->reference ?: ('EXP-' . $expense->getKey()),
                        'entity' => $expense->vendor ?: $expense->title,
                        'meta' => optional($expense->expense_date)->format('M d, Y') ?? 'Expense booked',
                        'category' => __('erp.common.expense'),
                        'category_bg' => '#ffdad6',
                        'category_fg' => '#93000a',
                        'value' => $this->money((float) $expense->amount),
                        'status' => __('erp.common.posted'),
                        'status_bg' => '#fff1ef',
                        'status_fg' => '#93000a',
                        'status_dot' => '#ba1a1a',
                        'pillar' => '#ba1a1a',
                        'timestamp' => $expense->created_at,
                    ])
            )
            ->sortByDesc('timestamp')
            ->take(6)
            ->values()
            ->map(function (array $entry): array {
                unset($entry['timestamp']);

                return $entry;
            })
            ->all();

        return $entries ?: $this->placeholderEntries();
    }

    protected function getMilestones(): array
    {
        if (!$this->hasTables(['projects'])) {
            return $this->placeholderMilestones();
        }

        $progressMap = [
            'planned' => 24,
            'active' => 68,
            'in_progress' => 74,
            'review' => 86,
            'completed' => 100,
        ];

        $milestones = Project::query()
            ->latest()
            ->take(3)
            ->get()
            ->map(function (Project $project) use ($progressMap): array {
                $progress = $progressMap[$project->status] ?? 52;

                return [
                    'name' => $project->name,
                    'stage' => str($project->status)->replace('_', ' ')->title()->toString(),
                    'progress' => $progress,
                    'color' => $progress >= 80 ? '#8df5e4' : '#adc7f7',
                ];
            })
            ->all();

        return $milestones ?: $this->placeholderMilestones();
    }

    protected function getHealth(): array
    {
        if (!$this->hasTables(['clients', 'invoices', 'payments'])) {
            return $this->placeholderHealth();
        }

        $invoiceTotal = (float) Invoice::query()->sum('total');
        $paymentTotal = (float) Payment::query()->sum('amount');
        $collectionRate = $invoiceTotal > 0 ? min(100, (int) round(($paymentTotal / $invoiceTotal) * 100)) : 0;
        $activeClients = Client::query()->whereIn('status', ['active', 'customer'])->count();
        $clientCount = max(Client::query()->count(), 1);
        $engagement = (int) round(($activeClients / $clientCount) * 100);
        $overdue = Invoice::query()->where('status', 'overdue')->count();
        $users = Schema::hasTable('users') ? \App\Models\User::query()->count() : 0;

        return [
            [
                'label' => __('erp.dashboard.health_collection_rate'),
                'value' => $collectionRate . '%',
                'note' => __('erp.dashboard.health_collection_rate_note'),
                'progress' => $collectionRate,
                'color' => '#8df5e4',
            ],
            [
                'label' => __('erp.dashboard.health_active_clients'),
                'value' => number_format($activeClients),
                'note' => __('erp.dashboard.health_engagement_note', ['rate' => $engagement]),
                'progress' => min(100, max(18, $engagement)),
                'color' => '#adc7f7',
            ],
            [
                'label' => __('erp.dashboard.health_admin_users'),
                'value' => number_format($users),
                'note' => __('erp.dashboard.health_admin_users_note'),
                'progress' => min(100, max(12, $users * 10)),
                'color' => '#d5e3fc',
            ],
            [
                'label' => __('erp.dashboard.health_overdue_invoices'),
                'value' => number_format($overdue),
                'note' => __('erp.dashboard.health_overdue_note'),
                'progress' => min(100, max(10, $overdue * 12)),
                'color' => '#ffb4ab',
            ],
        ];
    }

    protected function getProjectBoard(): array
    {
        if (!$this->hasTables(['projects'])) {
            return $this->placeholderBoard();
        }

        $columns = [
            [
                'key' => 'planned',
                'label' => __('erp.dashboard.board_planned'),
                'dot' => '#002045',
                'badge_bg' => '#dce9ff',
                'badge_fg' => '#002045',
                'accent' => '#1a365d',
                'statuses' => ['planned'],
            ],
            [
                'key' => 'in_progress',
                'label' => __('erp.dashboard.board_in_progress'),
                'dot' => '#455f88',
                'badge_bg' => '#e5eeff',
                'badge_fg' => '#002045',
                'accent' => '#455f88',
                'statuses' => ['active', 'in_progress', 'review'],
            ],
            [
                'key' => 'completed',
                'label' => __('erp.dashboard.board_completed'),
                'dot' => '#70d8c8',
                'badge_bg' => 'rgba(141, 245, 228, 0.2)',
                'badge_fg' => '#005048',
                'accent' => '#8df5e4',
                'statuses' => ['completed'],
            ],
        ];

        $board = collect($columns)
            ->map(function (array $column): array {
                $items = Project::query()
                    ->with(['client', 'assignee'])
                    ->whereIn('status', $column['statuses'])
                    ->latest()
                    ->get()
                    ->sortBy(fn(Project $project) => $project->due_date?->timestamp ?? PHP_INT_MAX)
                    ->take(4)
                    ->values()
                    ->map(fn(Project $project): array => $this->transformProject($project, $column['accent'], $column['key']))
                    ->all();

                return [
                    ...$column,
                    'count' => count($items),
                    'items' => $items,
                ];
            })
            ->all();

        return collect($board)->sum('count') > 0 ? $board : $this->placeholderBoard();
    }

    protected function transformProject(Project $project, string $accent, string $column): array
    {
        $client = $project->client?->company_name ?: $project->client?->contact_name ?: __('erp.resources.project.not_assigned_client');
        $assignee = $project->assignee?->name ?: __('erp.resources.project.ops_team');
        $progress = match ($project->status) {
            'planned' => 18,
            'active' => 58,
            'in_progress' => 72,
            'review' => 90,
            'completed' => 100,
            default => 46,
        };

        $category = match ($column) {
            'planned' => __('erp.dashboard.board_column_planning'),
            'in_progress' => __('erp.dashboard.board_column_execution'),
            'completed' => __('erp.dashboard.board_column_delivered'),
            default => __('erp.dashboard.board_column_delivery'),
        };

        return [
            'category' => $category,
            'reference' => 'CMX-' . str_pad((string) $project->getKey(), 3, '0', STR_PAD_LEFT),
            'title' => $project->name,
            'client' => $client,
            'description' => str($project->description ?: __('erp.resources.project.in_progress_hint'))->limit(82)->toString(),
            'assignee' => $assignee,
            'initials' => $this->initials($assignee),
            'due' => optional($project->due_date)->format('M d, Y') ?? __('erp.common.to_plan'),
            'progress' => $progress,
            'accent' => $accent,
            'show_progress' => $column === 'in_progress',
            'done' => $column === 'completed',
        ];
    }

    protected function getBoardSummary(): array
    {
        if (!$this->hasTables(['projects'])) {
            return $this->placeholderBoardSummary();
        }

        $total = Project::query()->count();
        $active = Project::query()->whereIn('status', ['active', 'in_progress', 'review'])->count();
        $completed = Project::query()->where('status', 'completed')->count();
        $efficiency = $total > 0 ? min(99, max(42, (int) round((($completed + ($active * 0.65)) / $total) * 100))) : 94;

        return [
            'organization' => CompanySetting::query()->value('company_name') ?: config('app.name', 'ERP'),
            'efficiency' => $efficiency,
            'headline' => __('erp.dashboard.efficiency_headline', ['rate' => $efficiency]),
            'active_contracts' => $active,
        ];
    }

    protected function getCriticalActions(): array
    {
        $actions = [];

        if ($this->hasTables(['projects'])) {
            $dueSoon = Project::query()
                ->with('client')
                ->whereNotIn('status', ['completed'])
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->take(2)
                ->get();

            foreach ($dueSoon as $project) {
                $actions[] = [
                    'tone' => $project->due_date && $project->due_date->isPast() ? 'danger' : 'info',
                    'title' => $project->name,
                    'note' => __('erp.dashboard.deadline', ['date' => optional($project->due_date)->format('M d, Y')]),
                ];
            }
        }

        if ($this->hasTables(['invoices'])) {
            $overdue = Invoice::query()->where('status', 'overdue')->count();

            if ($overdue > 0) {
                $actions[] = [
                    'tone' => 'danger',
                    'title' => __('erp.dashboard.overdue_followup', ['count' => number_format($overdue)]),
                    'note' => __('erp.dashboard.collections_attention'),
                ];
            }
        }

        $actions = array_slice($actions, 0, 3);

        return $actions ?: $this->placeholderActions();
    }

    protected function getTeam(): array
    {
        if (!$this->hasTables(['projects'])) {
            return $this->placeholderTeam();
        }

        $tones = ['#d5e3fc', '#e5eeff', '#dff8f0', '#ffedd5'];

        $team = Project::query()
            ->with('assignee')
            ->whereNotNull('assigned_to')
            ->latest()
            ->take(6)
            ->get()
            ->pluck('assignee.name')
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->map(function (string $name, int $index) use ($tones): array {
                return [
                    'name' => $name,
                    'initials' => $this->initials($name),
                    'tone' => $tones[$index % count($tones)],
                ];
            })
            ->all();

        return $team ?: $this->placeholderTeam();
    }

    protected function initials(string $name): string
    {
        return (string) str($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn(string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }

    protected function statusBg(string $status): string
    {
        return match ($status) {
            'paid' => '#dff8f0',
            'partially_paid' => '#eef4ff',
            'overdue', 'cancelled' => '#fff1ef',
            default => '#eef4ff',
        };
    }

    protected function statusFg(string $status): string
    {
        return match ($status) {
            'paid' => '#005048',
            'overdue', 'cancelled' => '#93000a',
            default => '#2d476f',
        };
    }

    protected function statusDot(string $status): string
    {
        return match ($status) {
            'paid' => '#43af9f',
            'overdue', 'cancelled' => '#ba1a1a',
            'partially_paid' => '#455f88',
            default => '#74777f',
        };
    }

    protected function pillar(string $status): string
    {
        return match ($status) {
            'paid' => '#8df5e4',
            'overdue', 'cancelled' => '#ba1a1a',
            'partially_paid' => '#adc7f7',
            default => '#002045',
        };
    }

    protected function hasTables(array $tables): bool
    {
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 0, '.', ' ');
    }

    protected function placeholderEntries(): array
    {
        return [
            [
                'reference' => '—',
                'entity' => __('erp.common.no_recent_activity'),
                'meta' => __('erp.common.no_recent_activity_hint'),
                'category' => __('erp.common.system'),
                'category_bg' => '#eff4ff',
                'category_fg' => '#002045',
                'value' => 'FCFA 0',
                'status' => __('erp.common.empty'),
                'status_bg' => '#eef0f4',
                'status_fg' => '#43474e',
                'status_dot' => '#74777f',
                'pillar' => '#adc7f7',
            ],
        ];
    }

    protected function placeholderMilestones(): array
    {
        return [
            ['name' => __('erp.resources.project.no_active_project'), 'stage' => __('erp.resources.project.project_feed_hint'), 'progress' => 0, 'color' => '#adc7f7'],
        ];
    }

    protected function placeholderHealth(): array
    {
        return [
            ['label' => __('erp.dashboard.health_collection_rate'), 'value' => '0%', 'note' => __('erp.dashboard.no_collection_data'), 'progress' => 0, 'color' => '#8df5e4'],
            ['label' => __('erp.dashboard.health_active_clients'), 'value' => '0', 'note' => __('erp.dashboard.no_active_clients'), 'progress' => 0, 'color' => '#adc7f7'],
            ['label' => __('erp.dashboard.health_admin_users'), 'value' => '0', 'note' => __('erp.dashboard.no_users'), 'progress' => 0, 'color' => '#d5e3fc'],
            ['label' => __('erp.dashboard.health_overdue_invoices'), 'value' => '0', 'note' => __('erp.dashboard.no_overdue'), 'progress' => 0, 'color' => '#ffb4ab'],
        ];
    }

    protected function placeholderBoard(): array
    {
        return [
            [
                'key' => 'planned',
                'label' => __('erp.dashboard.board_planned'),
                'dot' => '#002045',
                'badge_bg' => '#dce9ff',
                'badge_fg' => '#002045',
                'accent' => '#1a365d',
                'count' => 0,
                'items' => [],
            ],
            [
                'key' => 'in_progress',
                'label' => __('erp.dashboard.board_in_progress'),
                'dot' => '#455f88',
                'badge_bg' => '#e5eeff',
                'badge_fg' => '#002045',
                'accent' => '#455f88',
                'count' => 0,
                'items' => [],
            ],
            [
                'key' => 'completed',
                'label' => __('erp.dashboard.board_completed'),
                'dot' => '#70d8c8',
                'badge_bg' => 'rgba(141, 245, 228, 0.2)',
                'badge_fg' => '#005048',
                'accent' => '#8df5e4',
                'count' => 0,
                'items' => [],
            ],
        ];
    }

    protected function placeholderBoardSummary(): array
    {
        return [
            'organization' => CompanySetting::query()->value('company_name') ?: config('app.name', 'ERP'),
            'efficiency' => 0,
            'headline' => '0% d’efficacité sur le portefeuille projets',
            'active_contracts' => 0,
        ];
    }

    protected function placeholderActions(): array
    {
        return [
            [
                'tone' => 'info',
                'title' => 'Aucune action critique',
                'note' => 'Les alertes opérationnelles réelles apparaîtront ici.',
            ],
        ];
    }

    protected function placeholderTeam(): array
    {
        return [];
    }
}
