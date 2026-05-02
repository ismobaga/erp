<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

/**
 * Renders a company switcher in the Filament navigation bar.
 *
 * Users who belong to only one company will see a static label.
 * Users with multiple companies get a dropdown to switch context.
 */
class CompanySwitcher extends Widget
{
    protected string $view = 'filament.widgets.company-switcher';

    protected static bool $isLazy = false;

    /** Resolved once per render. */
    public ?Company $currentCompany = null;

    /** @var Company[] */
    public array $companies = [];

    public function mount(): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $this->companies = $user->companies()->orderBy('name')->get()->all();
        $this->currentCompany = currentCompany();
    }

    public function switchTo(int $companyId): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $company = $user->companies()->find($companyId);

        if ($company === null) {
            return;
        }

        session(['current_company_id' => $company->id]);
        app()->instance('currentCompany', $company);

        $this->currentCompany = $company;

        // Redirect to refresh the full page so every resource reloads under the new scope.
        $this->redirect(request()->header('Referer') ?? '/admin');
    }
}
