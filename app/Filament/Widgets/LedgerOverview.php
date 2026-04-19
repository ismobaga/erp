<?php

namespace App\Filament\Widgets;

use App\Models\Client;
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
                        'entity' => $invoice->client?->company_name ?: $invoice->client?->contact_name ?: 'Client account',
                        'meta' => optional($invoice->issue_date)->format('M d, Y') ?? 'Invoice issued',
                        'category' => 'Invoice',
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
                        'entity' => $quote->client?->company_name ?: $quote->client?->contact_name ?: 'Client account',
                        'meta' => optional($quote->issue_date)->format('M d, Y') ?? 'Proposal prepared',
                        'category' => 'Quote',
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
                        'entity' => $payment->client?->company_name ?: $payment->client?->contact_name ?: 'Client account',
                        'meta' => optional($payment->payment_date)->format('M d, Y') ?? 'Payment recorded',
                        'category' => 'Payment',
                        'category_bg' => '#d5e3fc',
                        'category_fg' => '#3a485b',
                        'value' => $this->money((float) $payment->amount),
                        'status' => 'Recorded',
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
                        'category' => 'Expense',
                        'category_bg' => '#ffdad6',
                        'category_fg' => '#93000a',
                        'value' => $this->money((float) $expense->amount),
                        'status' => 'Posted',
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
                'label' => 'Collection rate',
                'value' => $collectionRate . '%',
                'note' => 'Revenue converted to cash',
                'progress' => $collectionRate,
                'color' => '#8df5e4',
            ],
            [
                'label' => 'Active clients',
                'value' => number_format($activeClients),
                'note' => $engagement . '% engagement',
                'progress' => min(100, max(18, $engagement)),
                'color' => '#adc7f7',
            ],
            [
                'label' => 'Admin users',
                'value' => number_format($users),
                'note' => 'Secure system access',
                'progress' => min(100, max(12, $users * 10)),
                'color' => '#d5e3fc',
            ],
            [
                'label' => 'Overdue invoices',
                'value' => number_format($overdue),
                'note' => 'Requires follow-up',
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
                'label' => 'Planned',
                'dot' => '#002045',
                'badge_bg' => '#dce9ff',
                'badge_fg' => '#002045',
                'accent' => '#1a365d',
                'statuses' => ['planned'],
            ],
            [
                'key' => 'in_progress',
                'label' => 'In Progress',
                'dot' => '#455f88',
                'badge_bg' => '#e5eeff',
                'badge_fg' => '#002045',
                'accent' => '#455f88',
                'statuses' => ['active', 'in_progress', 'review'],
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
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
        $client = $project->client?->company_name ?: $project->client?->contact_name ?: 'Strategic account';
        $assignee = $project->assignee?->name ?: 'Operations team';
        $progress = match ($project->status) {
            'planned' => 18,
            'active' => 58,
            'in_progress' => 72,
            'review' => 90,
            'completed' => 100,
            default => 46,
        };

        $category = match ($column) {
            'planned' => 'Planning',
            'in_progress' => 'Execution',
            'completed' => 'Delivered',
            default => 'Delivery',
        };

        return [
            'category' => $category,
            'reference' => 'CMX-' . str_pad((string) $project->getKey(), 3, '0', STR_PAD_LEFT),
            'title' => $project->name,
            'client' => $client,
            'description' => str($project->description ?: 'Structured delivery track for the enterprise ledger.')->limit(82)->toString(),
            'assignee' => $assignee,
            'initials' => $this->initials($assignee),
            'due' => optional($project->due_date)->format('M d, Y') ?? 'TBD',
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
            'organization' => 'CROMMIX MALI S.A.',
            'efficiency' => $efficiency,
            'headline' => $efficiency . '% Project Delivery Efficiency',
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
                    'note' => 'Deadline ' . optional($project->due_date)->format('M d, Y'),
                ];
            }
        }

        if ($this->hasTables(['invoices'])) {
            $overdue = Invoice::query()->where('status', 'overdue')->count();

            if ($overdue > 0) {
                $actions[] = [
                    'tone' => 'danger',
                    'title' => number_format($overdue) . ' overdue invoices need follow-up',
                    'note' => 'Collections queue requires attention',
                ];
            }
        }

        $actions = array_slice($actions, 0, 3);

        return count($actions) >= 2 ? $actions : $this->placeholderActions();
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
                'reference' => '#INV-2024-081',
                'entity' => 'Sahel Tech Solutions',
                'meta' => 'Network deployment',
                'category' => 'Invoice',
                'category_bg' => '#d6e3ff',
                'category_fg' => '#2d476f',
                'value' => 'FCFA 2 450 000',
                'status' => 'Settled',
                'status_bg' => '#dff8f0',
                'status_fg' => '#005048',
                'status_dot' => '#43af9f',
                'pillar' => '#8df5e4',
            ],
            [
                'reference' => '#QT-2024-012',
                'entity' => 'Bamako Logistics',
                'meta' => 'Consulting audit',
                'category' => 'Quote',
                'category_bg' => '#eff4ff',
                'category_fg' => '#002045',
                'value' => 'FCFA 1 120 000',
                'status' => 'Draft',
                'status_bg' => '#eef0f4',
                'status_fg' => '#43474e',
                'status_dot' => '#74777f',
                'pillar' => '#adc7f7',
            ],
            [
                'reference' => '#EXP-004-PRJ',
                'entity' => 'Mali Materials S.A.',
                'meta' => 'Procurement - steel',
                'category' => 'Expense',
                'category_bg' => '#ffdad6',
                'category_fg' => '#93000a',
                'value' => 'FCFA 890 000',
                'status' => 'Posted',
                'status_bg' => '#fff1ef',
                'status_fg' => '#93000a',
                'status_dot' => '#ba1a1a',
                'pillar' => '#ba1a1a',
            ],
        ];
    }

    protected function placeholderMilestones(): array
    {
        return [
            ['name' => 'Sadiola Mine Phase 2', 'stage' => 'Site excavation', 'progress' => 75, 'color' => '#8df5e4'],
            ['name' => 'Bamako Sky Tower', 'stage' => 'Structural foundation', 'progress' => 32, 'color' => '#adc7f7'],
            ['name' => 'Industrial Hub C', 'stage' => 'Final commissioning', 'progress' => 94, 'color' => '#8df5e4'],
        ];
    }

    protected function placeholderHealth(): array
    {
        return [
            ['label' => 'Collection rate', 'value' => '98%', 'note' => 'Revenue converted to cash', 'progress' => 98, 'color' => '#8df5e4'],
            ['label' => 'Active clients', 'value' => '124', 'note' => '87% engagement', 'progress' => 87, 'color' => '#adc7f7'],
            ['label' => 'Admin users', 'value' => '12', 'note' => 'Secure system access', 'progress' => 42, 'color' => '#d5e3fc'],
            ['label' => 'Overdue invoices', 'value' => '4', 'note' => 'Requires follow-up', 'progress' => 24, 'color' => '#ffb4ab'],
        ];
    }

    protected function placeholderBoard(): array
    {
        return [
            [
                'key' => 'planned',
                'label' => 'Planned',
                'dot' => '#002045',
                'badge_bg' => '#dce9ff',
                'badge_fg' => '#002045',
                'accent' => '#1a365d',
                'count' => 2,
                'items' => [
                    [
                        'category' => 'Logistics',
                        'reference' => 'CMX-402',
                        'title' => 'Mali North Expansion Phase II',
                        'client' => 'Sahel Resources Ltd.',
                        'description' => 'Execution planning for the second expansion corridor.',
                        'assignee' => 'Amadou Keita',
                        'initials' => 'AK',
                        'due' => 'Oct 24, 2023',
                        'progress' => 18,
                        'accent' => '#1a365d',
                        'show_progress' => false,
                        'done' => false,
                    ],
                    [
                        'category' => 'Consulting',
                        'reference' => 'CMX-405',
                        'title' => 'ISO Audit Preparation',
                        'client' => 'Bamako Mining Group',
                        'description' => 'Operational readiness and compliance preparation track.',
                        'assignee' => 'Fatima Traoré',
                        'initials' => 'FT',
                        'due' => 'Nov 12, 2023',
                        'progress' => 22,
                        'accent' => '#1a365d',
                        'show_progress' => false,
                        'done' => false,
                    ],
                ],
            ],
            [
                'key' => 'in_progress',
                'label' => 'In Progress',
                'dot' => '#455f88',
                'badge_bg' => '#e5eeff',
                'badge_fg' => '#002045',
                'accent' => '#455f88',
                'count' => 2,
                'items' => [
                    [
                        'category' => 'Supply Chain',
                        'reference' => 'CMX-398',
                        'title' => 'Heavy Equipment Procurement',
                        'client' => 'Global Infra S.A.',
                        'description' => 'Vendor alignment and contract execution in motion.',
                        'assignee' => 'Moussa Sidibé',
                        'initials' => 'MS',
                        'due' => 'Sep 30, 2023',
                        'progress' => 65,
                        'accent' => '#455f88',
                        'show_progress' => true,
                        'done' => false,
                    ],
                    [
                        'category' => 'Tech Infrastructure',
                        'reference' => 'CMX-388',
                        'title' => 'Data Center Cooling Upgrade',
                        'client' => 'Mali Telecom',
                        'description' => 'Infrastructure modernization is underway.',
                        'assignee' => 'Sarah Kone',
                        'initials' => 'SK',
                        'due' => 'Oct 15, 2023',
                        'progress' => 22,
                        'accent' => '#455f88',
                        'show_progress' => true,
                        'done' => false,
                    ],
                ],
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'dot' => '#70d8c8',
                'badge_bg' => 'rgba(141, 245, 228, 0.2)',
                'badge_fg' => '#005048',
                'accent' => '#8df5e4',
                'count' => 1,
                'items' => [
                    [
                        'category' => 'Maintenance',
                        'reference' => 'CMX-350',
                        'title' => 'River Port Dredging Project',
                        'client' => 'Ministry of Transport',
                        'description' => 'Completed and signed off by all delivery stakeholders.',
                        'assignee' => 'Ibrahim Diallo',
                        'initials' => 'ID',
                        'due' => 'Aug 12, 2023',
                        'progress' => 100,
                        'accent' => '#8df5e4',
                        'show_progress' => false,
                        'done' => true,
                    ],
                ],
            ],
        ];
    }

    protected function placeholderBoardSummary(): array
    {
        return [
            'organization' => 'CROMMIX MALI S.A.',
            'efficiency' => 94,
            'headline' => '94% Project Delivery Efficiency',
            'active_contracts' => 12,
        ];
    }

    protected function placeholderActions(): array
    {
        return [
            [
                'tone' => 'danger',
                'title' => 'Contract CMX-388 pending approval',
                'note' => 'Due within the next operating cycle',
            ],
            [
                'tone' => 'success',
                'title' => 'Weekly stakeholder sync scheduled',
                'note' => 'Today at 14:00 GMT',
            ],
        ];
    }

    protected function placeholderTeam(): array
    {
        return [
            ['name' => 'Fatima Traoré', 'initials' => 'FT', 'tone' => '#d5e3fc'],
            ['name' => 'Moussa Sidibé', 'initials' => 'MS', 'tone' => '#e5eeff'],
            ['name' => 'Sarah Kone', 'initials' => 'SK', 'tone' => '#dff8f0'],
            ['name' => 'Ibrahim Diallo', 'initials' => 'ID', 'tone' => '#ffedd5'],
        ];
    }
}
