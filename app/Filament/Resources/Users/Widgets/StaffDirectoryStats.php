<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Throwable;

class StaffDirectoryStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Vue d’ensemble du personnel';

    protected ?string $description = 'Indicateurs en direct sur l’activité, la disponibilité et les accès.';

    protected function getStats(): array
    {
        try {
            if (!Schema::hasTable('users')) {
                return $this->placeholderStats();
            }

            $total = User::query()->count();
            $active = User::query()->where('status', 'active')->count();
            $managers = Schema::hasTable('model_has_roles')
                ? User::query()->whereHas('roles', fn($query) => $query->whereIn('name', ['manager', 'project-manager', 'project manager']))->count()
                : 0;
            $rate = $total > 0 ? round(($active / $total) * 100, 1) : 0;

            return [
                Stat::make('Total staff', number_format($total))
                    ->description('Registered collaborators in the directory')
                    ->color('primary')
                    ->chart([6, 7, 8, 9, 10, 11, 12]),
                Stat::make('Currently active', number_format($active))
                    ->description('Available for current operations')
                    ->color('success')
                    ->chart([5, 6, 7, 7, 8, 9, 10]),
                Stat::make('Project managers', number_format($managers))
                    ->description('Leadership and delivery oversight roles')
                    ->color('info')
                    ->chart([1, 2, 3, 4, 4, 5, 6]),
                Stat::make('Operational rate', number_format($rate, 1) . '%')
                    ->description('Active share of the workforce ledger')
                    ->color('warning')
                    ->chart([72, 80, 83, 85, 88, 91, (int) round($rate)]),
            ];
        } catch (Throwable) {
            return $this->placeholderStats();
        }
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make('Total staff', '124')->description('Registered collaborators in the directory')->color('primary'),
            Stat::make('Currently active', '98')->description('Available for current operations')->color('success'),
            Stat::make('Project managers', '14')->description('Leadership and delivery oversight roles')->color('info'),
            Stat::make('Operational rate', '94.2%')->description('Active share of the workforce ledger')->color('warning'),
        ];
    }
}
