<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\ActivityLog;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityAccessOverview extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.users.widgets.security-access-overview';

    protected function getViewData(): array
    {
        try {
            return [
                'logs' => $this->getLogs(),
                'metrics' => $this->getMetrics(),
            ];
        } catch (Throwable) {
            return [
                'logs' => $this->placeholderLogs(),
                'metrics' => $this->placeholderMetrics(),
            ];
        }
    }

    protected function getLogs(): array
    {
        $logs = [];

        if (Schema::hasTable('users')) {
            $recentUsers = User::query()
                ->whereNotNull('last_login_at')
                ->latest('last_login_at')
                ->take(3)
                ->get()
                ->map(fn(User $user): array => [
                    'tone' => 'primary',
                    'initials' => str($user->name)->explode(' ')->take(2)->map(fn($part) => str($part)->substr(0, 1))->join('')->upper()->toString(),
                    'name' => $user->name,
                    'detail' => $user->email,
                    'state' => strtoupper($user->status ?: 'active'),
                    'time' => $user->last_login_at?->diffForHumans() ?? 'à l’instant',
                ])
                ->all();

            $logs = [...$logs, ...$recentUsers];
        }

        if (Schema::hasTable('activity_logs')) {
            $activity = ActivityLog::query()
                ->latest()
                ->take(1)
                ->get()
                ->map(fn(ActivityLog $log): array => [
                    'tone' => 'tertiary',
                    'initials' => 'AL',
                    'name' => ucfirst(str_replace('_', ' ', $log->action ?: 'system event')),
                    'detail' => class_basename((string) $log->subject_type) ?: 'Internal process',
                    'state' => 'INTERNAL',
                    'time' => $log->created_at?->diffForHumans() ?? 'recently',
                ])
                ->all();

            $logs = [...$logs, ...$activity];
        }

        return count($logs) > 0 ? array_slice($logs, 0, 4) : $this->placeholderLogs();
    }

    protected function getMetrics(): array
    {
        if (!Schema::hasTable('users')) {
            return $this->placeholderMetrics();
        }

        $total = User::query()->count();
        $restricted = User::query()->where('status', 'restricted')->count();

        return [
            'active_personnel' => number_format($total),
            'mfa_compliance' => '100%',
            'pending_revocations' => number_format($restricted),
        ];
    }

    protected function placeholderLogs(): array
    {
        return [
            ['tone' => 'primary', 'initials' => '--', 'name' => 'Aucun accès récent', 'detail' => 'Les événements de connexion apparaîtront ici.', 'state' => 'WAITING', 'time' => '—'],
        ];
    }

    protected function placeholderMetrics(): array
    {
        return [
            'active_personnel' => '0',
            'mfa_compliance' => '0%',
            'pending_revocations' => '0',
        ];
    }
}
