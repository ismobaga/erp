<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Observers\CreditNoteObserver;
use App\Observers\ExpenseObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('contact', function (Request $request): Limit {
            $email = strtolower((string) $request->input('email', 'guest'));

            return Limit::perHour(3)->by($request->ip() . '|' . $email);
        });

        RateLimiter::for('pdf', function (Request $request): Limit {
            return Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('portal', function (Request $request): Limit {
            $token = (string) $request->route('token', 'portal');

            return Limit::perHour(60)->by($request->ip() . '|' . $token);
        });

        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
        Expense::observe(ExpenseObserver::class);
        CreditNote::observe(CreditNoteObserver::class);

        // Enforce consistent action semantics across the whole admin panel.
        CreateAction::configureUsing(fn(CreateAction $action) => $action->defaultColor('primary'));
        EditAction::configureUsing(fn(EditAction $action) => $action->defaultColor('gray'));
        ViewAction::configureUsing(fn(ViewAction $action) => $action->defaultColor('gray'));
        DeleteAction::configureUsing(fn(DeleteAction $action) => $action->defaultColor('danger'));
        DeleteBulkAction::configureUsing(fn(DeleteBulkAction $action) => $action->defaultColor('danger'));

        Action::configureUsing(function (Action $action): void {
            if ($action->getColor() !== null) {
                return;
            }

            $name = strtolower((string) $action->getName());

            if ($name === '') {
                return;
            }

            if (str_contains($name, 'delete') || str_contains($name, 'remove') || str_contains($name, 'logout') || str_contains($name, 'void')) {
                $action->defaultColor('danger');

                return;
            }

            if (str_contains($name, 'approve') || str_contains($name, 'complete') || str_contains($name, 'send') || str_contains($name, 'invite') || str_contains($name, 'reconcile') || str_contains($name, 'save') || str_contains($name, 'create') || str_contains($name, 'add')) {
                $action->defaultColor('success');

                return;
            }

            if (str_contains($name, 'reopen') || str_contains($name, 'reconnect') || str_contains($name, 'review') || str_contains($name, 'check') || str_contains($name, 'run')) {
                $action->defaultColor('warning');

                return;
            }

            if (str_contains($name, 'export') || str_contains($name, 'download') || str_contains($name, 'details') || str_contains($name, 'back') || str_contains($name, 'status') || str_contains($name, 'list')) {
                $action->defaultColor('gray');
            }
        });
    }
}
