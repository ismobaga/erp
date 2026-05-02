<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Serves public-facing marketing and legal pages for the company.
 *
 * Company data is resolved from the current tenant context when available,
 * falling back to the first CompanySetting row for single-tenant deployments.
 */
class CompanyPagesController extends Controller
{
    public function presentation(): View
    {
        $company = $this->settings();

        return view('company.presentation', $this->viewData($company));
    }

    public function confidentialite(): View
    {
        return view('company.confidentialite', $this->viewData($this->settings()));
    }

    public function conditions(): View
    {
        return view('company.conditions', $this->viewData($this->settings()));
    }

    public function cookies(): View
    {
        return view('company.cookies', $this->viewData($this->settings()));
    }

    public function bureaux(): View
    {
        return view('company.bureaux', $this->viewData($this->settings()));
    }

    public function dmsPresentation(): View
    {
        $company = $this->settings();

        return view('company.dms-presentation', $this->viewData($company));
    }

    public function contactRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'intent'       => ['required', 'string', 'max:255'],
            'message'      => ['nullable', 'string', 'max:2000'],
            'source'       => ['nullable', 'in:website,dms'],
        ]);

        $source = $validated['source'] ?? 'website';

        ContactRequest::create([
            'name'         => $validated['name'],
            'company_name' => $validated['company_name'] ?? null,
            'email'        => $validated['email'],
            'intent'       => $validated['intent'],
            'message'      => $validated['message'] ?? null,
            'status'       => 'new',
            'source'       => $source,
        ]);

        $redirectTarget = $source === 'dms'
            ? url('/dms-presentation') . '/#contact'
            : url('/') . '/#contact';

        return redirect()->to($redirectTarget)->with(
            'status',
            'Merci ' . e($validated['name']) . ' — votre demande a bien été reçue. Nous vous recontacterons rapidement.'
        );
    }

    /**
     * Resolve the active company settings.
     *
     * When a company is bound in the IoC container (authenticated admin
     * context), its scoped settings are used.  For public pages we fall back
     * to the first available settings row.
     */
    protected function settings(): ?CompanySetting
    {
        return CompanySetting::withoutCompanyScope()->first();
    }

    /**
     * Build a standard view data array from a CompanySetting instance.
     */
    protected function viewData(?CompanySetting $company): array
    {
        return [
            'company'        => $company,
            'companyName'    => $company?->company_name ?: config('app.name'),
            'companyEmail'   => $company?->email ?: '',
            'companyPhone'   => $company?->phone ?: '',
            'companyAddress' => trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')),
            'companyWebsite' => $company?->website ?: '',
        ];
    }
}
