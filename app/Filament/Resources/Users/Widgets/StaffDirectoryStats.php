<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
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
                    ->chart($this->countTrend(fn($query) => $query)),
                Stat::make('Currently active', number_format($active))
                    ->description('Available for current operations')
                    ->color('success')
                    ->chart($this->countTrend(fn($query) => $query->where('status', 'active'))),
                Stat::make('Project managers', number_format($managers))
                    ->description('Leadership and delivery oversight roles')
                    ->color('info')
                    ->chart($this->managerTrend()),
                Stat::make('Operational rate', number_format($rate, 1) . '%')
                    ->description('Active share of the workforce ledger')
                    ->color('warning')
                    ->chart($this->rateTrend()),
            ];
        } catch (Throwable) {
            return $this->placeholderStats();
        }
    }

    protected function countTrend(callable $scope): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset) use ($scope): int {
                $start = now()->copy()->subMonths($offset)->startOfMonth();
                $end = now()->copy()->subMonths($offset)->endOfMonth();
                $query = DB::table('users')->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()]);
                $scope($query);

                return (int) $query->count();
            })
            ->all();
    }

    protected function rateTrend(): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset): int {
                $checkpoint = now()->copy()->subMonths($offset)->endOfMonth();
                $total = DB::table('users')->where('created_at', '<=', $checkpoint->toDateTimeString())->count();
                $active = DB::table('users')
                    ->where('created_at', '<=', $checkpoint->toDateTimeString())
                    ->where('status', 'active')
                    ->count();

                return $total > 0 ? (int) round(($active / $total) * 100) : 0;
            })
            ->all();
    }

    protected function managerTrend(): array
    {
        if (!Schema::hasTable('model_has_roles') || !Schema::hasTable('roles')) {
            return array_fill(0, 7, 0);
        }

        return collect(range(6, 0))
            ->map(function (int $offset): int {
                $start = now()->copy()->subMonths($offset)->startOfMonth();
                $end = now()->copy()->subMonths($offset)->endOfMonth();

                return User::query()
                    ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
                    ->whereHas('roles', fn($query) => $query->whereIn('name', ['manager', 'project-manager', 'project manager', 'Project Manager']))
                    ->count();
            })
            ->all();
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make('Total staff', '0')->description('Aucun collaborateur enregistré')->color('primary')->chart(array_fill(0, 7, 0)),
            Stat::make('Currently active', '0')->description('Aucun utilisateur actif')->color('success')->chart(array_fill(0, 7, 0)),
            Stat::make('Project managers', '0')->description('Aucun responsable projet détecté')->color('info')->chart(array_fill(0, 7, 0)),
            Stat::make('Operational rate', '0.0%')->description('Taux opérationnel indisponible')->color('warning')->chart(array_fill(0, 7, 0)),
        ];
    }
}
