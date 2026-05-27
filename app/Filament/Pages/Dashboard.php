<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountingPeriodsOverview;
use App\Filament\Widgets\ArchitecturalStatsOverview;
use App\Filament\Widgets\LedgerOverview;
use App\Filament\Widgets\OnboardingChecklistWidget;
use App\Filament\Widgets\OperationalResilienceOverview;
use App\Filament\Widgets\QuickActionsActivityWidget;
use App\Support\ErpEdition;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord';

    public static function canAccess(): bool
    {
        if (! ErpEdition::isModuleEnabled('dashboard')) {
            return false;
        }

        $user = auth()->user();

        if (! $user || $user->status === 'restricted') {
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
            ['class' => OnboardingChecklistWidget::class, 'module' => 'dashboard', 'feature' => null],
            ['class' => QuickActionsActivityWidget::class, 'module' => 'dashboard', 'feature' => null],
            ['class' => ArchitecturalStatsOverview::class, 'module' => 'dashboard', 'feature' => null],
            ['class' => AccountingPeriodsOverview::class, 'module' => 'financial_periods', 'feature' => 'financial_periods'],
            ['class' => OperationalResilienceOverview::class, 'module' => 'reports', 'feature' => 'advanced_reports'],
            ['class' => LedgerOverview::class, 'module' => 'ledger', 'feature' => 'general_ledger'],
        ];

        if (ErpEdition::isSimple()) {
            $widgets = array_values(array_filter(
                $widgets,
                fn (array $widget): bool => $widget['class'] !== OperationalResilienceOverview::class
            ));
        }

        return collect($widgets)
            ->filter(fn (array $widget): bool => ErpEdition::isModuleEnabled($widget['module']))
            ->filter(fn (array $widget): bool => blank($widget['feature']) || company_feature_enabled((string) $widget['feature']))
            ->map(fn (array $widget): string => $widget['class'])
            ->values()
            ->all();
    }
}
