<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountingPeriodsOverview;
use App\Filament\Widgets\ArchitecturalStatsOverview;
use App\Filament\Widgets\LedgerOverview;
use App\Filament\Widgets\OperationalResilienceOverview;
use App\Support\ErpEdition;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord';

    public static function canAccess(): bool
    {
        if (!ErpEdition::isModuleEnabled('dashboard')) {
            return false;
        }

        $user = auth()->user();

        if (!$user || $user->status === 'restricted') {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->getAllPermissions()->isNotEmpty() || $user->roles()->exists();
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 4,
            'xl' => 12,
        ];
    }

    public function getWidgets(): array
    {
        $widgets = [
            ['class' => ArchitecturalStatsOverview::class, 'module' => 'dashboard'],
            ['class' => AccountingPeriodsOverview::class, 'module' => 'financial_periods'],
            ['class' => OperationalResilienceOverview::class, 'module' => 'reports'],
            ['class' => LedgerOverview::class, 'module' => 'ledger'],
        ];

        return collect($widgets)
            ->filter(fn(array $widget): bool => ErpEdition::isModuleEnabled($widget['module']))
            ->map(fn(array $widget): string => $widget['class'])
            ->values()
            ->all();
    }
}
