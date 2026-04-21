<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LedgerAccount;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GrandLivre extends Page
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'ledger';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Grand Livre';

    protected static ?string $title = 'Grand Livre';

    protected static ?string $slug = 'grand-livre';

    protected string $view = 'filament.pages.grand-livre';

    public string $period = 'all';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->period = (string) request()->query('period', 'all');
        $this->statusFilter = (string) request()->query('status', 'all');
    }

    protected function getViewData(): array
    {
        try {
            return [
                'kpis'           => $this->getKpis(),
                'entries'        => $this->getEntries(),
                'accountSummary' => $this->getAccountSummary(),
                'periodOptions'  => $this->periodOptions(),
                'statusOptions'  => $this->statusOptions(),
            ];
        } catch (Throwable) {
            return [
                'kpis'           => $this->placeholderKpis(),
                'entries'        => [],
                'accountSummary' => [],
                'periodOptions'  => $this->periodOptions(),
                'statusOptions'  => $this->statusOptions(),
            ];
        }
    }

    // ── KPIs ─────────────────────────────────────────────────────────────────

    protected function getKpis(): array
    {
        if (!Schema::hasTable('journal_entries')) {
            return $this->placeholderKpis();
        }

        $query = JournalEntry::query();
        $this->applyPeriod($query);
        $this->applyStatus($query);

        $total   = $query->count();
        $posted  = (clone $query)->where('status', 'posted')->count();
        $draft   = (clone $query)->where('status', 'draft')->count();
        $voided  = (clone $query)->where('status', 'voided')->count();

        $lineQuery = JournalEntryLine::query()
            ->whereHas('entry', function ($q): void {
                $this->applyPeriod($q);
                $this->applyStatus($q);
            });

        $totalDebit  = (float) $lineQuery->sum('debit');
        $totalCredit = (float) (clone $lineQuery)->sum('credit');

        return [
            [
                'label'     => 'Total écritures',
                'value'     => $total,
                'note'      => $posted . ' validées · ' . $draft . ' brouillons · ' . $voided . ' annulées',
                'icon'      => 'heroicon-o-document-chart-bar',
                'color'     => '#002045',
                'bg'        => '#eff4ff',
            ],
            [
                'label'     => 'Total débit',
                'value'     => $this->money($totalDebit),
                'note'      => 'Cumul des mouvements débiteurs',
                'icon'      => 'heroicon-o-arrow-trending-up',
                'color'     => '#005048',
                'bg'        => '#dff7f0',
            ],
            [
                'label'     => 'Total crédit',
                'value'     => $this->money($totalCredit),
                'note'      => 'Cumul des mouvements créditeurs',
                'icon'      => 'heroicon-o-arrow-trending-down',
                'color'     => '#7c2d12',
                'bg'        => '#fde8d8',
            ],
            [
                'label'     => 'Écritures validées',
                'value'     => $posted,
                'note'      => round($total > 0 ? ($posted / $total) * 100 : 0, 1) . '% du total',
                'icon'      => 'heroicon-o-check-circle',
                'color'     => '#1a365d',
                'bg'        => '#d6e3ff',
            ],
        ];
    }

    protected function placeholderKpis(): array
    {
        return [
            ['label' => 'Total écritures',    'value' => '—', 'note' => 'Données non disponibles', 'icon' => 'heroicon-o-document-chart-bar', 'color' => '#002045', 'bg' => '#eff4ff'],
            ['label' => 'Total débit',         'value' => '—', 'note' => 'Données non disponibles', 'icon' => 'heroicon-o-arrow-trending-up',   'color' => '#005048', 'bg' => '#dff7f0'],
            ['label' => 'Total crédit',        'value' => '—', 'note' => 'Données non disponibles', 'icon' => 'heroicon-o-arrow-trending-down', 'color' => '#7c2d12', 'bg' => '#fde8d8'],
            ['label' => 'Écritures validées',  'value' => '—', 'note' => 'Données non disponibles', 'icon' => 'heroicon-o-check-circle',        'color' => '#1a365d', 'bg' => '#d6e3ff'],
        ];
    }

    // ── Journal entries ───────────────────────────────────────────────────────

    protected function getEntries(): array
    {
        if (!Schema::hasTable('journal_entries')) {
            return [];
        }

        $query = JournalEntry::query()->with(['lines.account', 'creator']);
        $this->applyPeriod($query);
        $this->applyStatus($query);

        return $query
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->take(50)
            ->get()
            ->map(fn(JournalEntry $e): array => [
                'entry_number' => $e->entry_number ?? '—',
                'entry_date'   => $e->entry_date?->format('d/m/Y') ?? '—',
                'description'  => $e->description ?? '—',
                'status'       => $e->status,
                'status_label' => $e->statusLabel(),
                'source_type'  => $e->source_type,
                'source_label' => $this->sourceLabel($e->source_type),
                'total_debit'  => $this->money($e->totalDebit()),
                'total_credit' => $this->money($e->totalCredit()),
                'balanced'     => $e->isBalanced(),
                'creator'      => $e->creator?->name ?? '—',
            ])
            ->toArray();
    }

    // ── Account summary ───────────────────────────────────────────────────────

    protected function getAccountSummary(): array
    {
        if (!Schema::hasTable('ledger_accounts') || !Schema::hasTable('journal_entry_lines')) {
            return [];
        }

        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];

        $rows = DB::table('ledger_accounts as a')
            ->join('journal_entry_lines as l', 'l.account_id', '=', 'a.id')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.status', 'posted')
            ->whereIn('a.type', $types)
            ->select('a.type', DB::raw('SUM(l.debit) as total_debit'), DB::raw('SUM(l.credit) as total_credit'))
            ->groupBy('a.type')
            ->get()
            ->keyBy('type');

        $typeColors = [
            'asset'     => ['bg' => '#dff7f0', 'fg' => '#005048', 'label' => 'Actif'],
            'liability' => ['bg' => '#fde8d8', 'fg' => '#7c2d12', 'label' => 'Passif'],
            'equity'    => ['bg' => '#d6e3ff', 'fg' => '#002045', 'label' => 'Capitaux propres'],
            'revenue'   => ['bg' => '#dff7f0', 'fg' => '#005048', 'label' => 'Produits'],
            'expense'   => ['bg' => '#fde8d8', 'fg' => '#7c2d12', 'label' => 'Charges'],
        ];

        $result = [];
        foreach ($types as $type) {
            $row = $rows->get($type);
            $debit  = $row ? (float) $row->total_debit  : 0.0;
            $credit = $row ? (float) $row->total_credit : 0.0;
            $net    = ($type === 'asset' || $type === 'expense') ? $debit - $credit : $credit - $debit;

            $result[] = [
                'type'         => $type,
                'label'        => $typeColors[$type]['label'],
                'total_debit'  => $this->money($debit),
                'total_credit' => $this->money($credit),
                'net_balance'  => $this->money($net),
                'net_positive' => $net >= 0,
                'bg'           => $typeColors[$type]['bg'],
                'fg'           => $typeColors[$type]['fg'],
            ];
        }

        return $result;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function applyPeriod(mixed $query): void
    {
        $now       = now();
        $lastMonth = $now->copy()->subMonth();
        $lastYear  = $now->copy()->subYear();

        match ($this->period) {
            'current_month' => $query->whereMonth('entry_date', $now->month)->whereYear('entry_date', $now->year),
            'last_month'    => $query->whereMonth('entry_date', $lastMonth->month)->whereYear('entry_date', $lastMonth->year),
            'current_year'  => $query->whereYear('entry_date', $now->year),
            'last_year'     => $query->whereYear('entry_date', $lastYear->year),
            default         => null,
        };
    }

    protected function sourceLabel(?string $sourceType): string
    {
        if ($sourceType === null) {
            return '—';
        }

        $translated = (string) __('erp.ledger.source_types.' . $sourceType, [], null);

        return $translated !== '' ? $translated : $sourceType;
    }

    protected function applyStatus(mixed $query): void
    {
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }
    }

    protected function periodOptions(): array
    {
        return [
            'all'           => 'Toutes les périodes',
            'current_month' => 'Mois en cours',
            'last_month'    => 'Mois précédent',
            'current_year'  => 'Année en cours',
            'last_year'     => 'Année précédente',
        ];
    }

    protected function statusOptions(): array
    {
        return [
            'all'    => 'Tous les statuts',
            'draft'  => 'Brouillon',
            'posted' => 'Validée',
            'voided' => 'Annulée',
        ];
    }

    protected function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' FCFA';
    }
}
