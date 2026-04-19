<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ArchitecturalStatsOverview;
use App\Filament\Widgets\LedgerOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord';

    public function getColumns(): int|array
    {
        return [
            'md' => 4,
            'xl' => 12,
        ];
    }

    public function getWidgets(): array
    {
        return [
            ArchitecturalStatsOverview::class,
            LedgerOverview::class,
        ];
    }
}
