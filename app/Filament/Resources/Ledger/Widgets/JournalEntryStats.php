<?php

namespace App\Filament\Resources\Ledger\Widgets;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class JournalEntryStats extends Widget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.ledger.widgets.journal-entry-stats';

    protected function getViewData(): array
    {
        try {
            if (!Schema::hasTable('journal_entries') || !Schema::hasTable('journal_entry_lines')) {
                return $this->placeholderData();
            }

            $total  = JournalEntry::query()->count();
            $posted = JournalEntry::query()->where('status', 'posted')->count();
            $draft  = JournalEntry::query()->where('status', 'draft')->count();
            $voided = JournalEntry::query()->where('status', 'voided')->count();

            $totalDebit  = (float) JournalEntryLine::query()
                ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
                ->sum('debit');

            $totalCredit = (float) JournalEntryLine::query()
                ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
                ->sum('credit');

            $sourceBreakdown = $this->sourceBreakdown($total);

            $trend = $this->monthlyTrend();

            return [
                'total'           => number_format($total),
                'posted'          => number_format($posted),
                'draft'           => number_format($draft),
                'voided'          => number_format($voided),
                'postedPct'       => $total > 0 ? round(($posted / $total) * 100, 1) : 0,
                'totalDebit'      => $this->money($totalDebit),
                'totalCredit'     => $this->money($totalCredit),
                'balanced'        => abs($totalDebit - $totalCredit) < 0.01,
                'sourceBreakdown' => $sourceBreakdown,
                'trend'           => $trend,
            ];
        } catch (Throwable) {
            return $this->placeholderData();
        }
    }

    // ── Source breakdown ──────────────────────────────────────────────────────

    protected function sourceBreakdown(int $total): array
    {
        $sourceColors = [
            'invoice'     => ['bg' => '#d6e3ff', 'fg' => '#2d476f'],
            'payment'     => ['bg' => '#dff7f0', 'fg' => '#005048'],
            'expense'     => ['bg' => '#fde8d8', 'fg' => '#7c2d12'],
            'credit_note' => ['bg' => '#f3e8ff', 'fg' => '#4a1672'],
            'manual'      => ['bg' => '#f3f4f6', 'fg' => '#43474e'],
        ];

        $rows = DB::table('journal_entries')
            ->selectRaw("COALESCE(source_type, 'manual') as source_type, COUNT(*) as cnt")
            ->groupBy(DB::raw("COALESCE(source_type, 'manual')"))
            ->orderByDesc('cnt')
            ->get();

        return $rows->map(function ($row) use ($total, $sourceColors): array {
            $key    = $row->source_type;
            $count  = (int) $row->cnt;
            $pct    = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
            $colors = $sourceColors[$key] ?? ['bg' => '#eff4ff', 'fg' => '#002045'];
            $label  = (string) __('erp.ledger.source_types.' . $key, []);

            if ($label === '') {
                $label = ucfirst(str_replace('_', ' ', $key));
            }

            return [
                'key'   => $key,
                'label' => $label,
                'count' => number_format($count),
                'pct'   => $pct,
                'bg'    => $colors['bg'],
                'fg'    => $colors['fg'],
            ];
        })->toArray();
    }

    // ── Monthly trend (last 7 months) ─────────────────────────────────────────

    protected function monthlyTrend(): array
    {
        if (!Schema::hasTable('journal_entries')) {
            return array_fill(0, 7, ['label' => '—', 'posted' => 0, 'draft' => 0, 'height_posted' => 0, 'height_draft' => 0, 'active' => false]);
        }

        $months = collect(range(6, 0))->map(function (int $offset): array {
            $date  = now()->copy()->subMonths($offset);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end   = $date->copy()->endOfMonth()->toDateString();

            $posted = (int) DB::table('journal_entries')
                ->where('status', 'posted')
                ->whereBetween('entry_date', [$start, $end])
                ->count();

            $draft = (int) DB::table('journal_entries')
                ->where('status', 'draft')
                ->whereBetween('entry_date', [$start, $end])
                ->count();

            return [
                'label'  => $date->format('M'),
                'posted' => $posted,
                'draft'  => $draft,
                'active' => $offset === 0,
            ];
        })->all();

        $max = max(1, ...array_map(fn (array $m): int => $m['posted'] + $m['draft'], $months));

        return array_map(function (array $m) use ($max): array {
            $m['height_posted'] = (int) round(($m['posted'] / $max) * 120);
            $m['height_draft']  = (int) round(($m['draft']  / $max) * 120);

            return $m;
        }, $months);
    }

    // ── Placeholder ───────────────────────────────────────────────────────────

    protected function placeholderData(): array
    {
        $emptyMonth = ['label' => '—', 'posted' => 0, 'draft' => 0, 'height_posted' => 0, 'height_draft' => 0, 'active' => false];

        return [
            'total'           => '—',
            'posted'          => '—',
            'draft'           => '—',
            'voided'          => '—',
            'postedPct'       => 0,
            'totalDebit'      => '— FCFA',
            'totalCredit'     => '— FCFA',
            'balanced'        => true,
            'sourceBreakdown' => [],
            'trend'           => array_fill(0, 7, $emptyMonth),
        ];
    }

    protected function money(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' FCFA';
    }
}
