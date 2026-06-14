<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Serves public-facing marketing and legal pages for the company.
 *
 * Company data is resolved from the current tenant context when available,
 * falling back to the first active Company row for single-tenant deployments.
 */
class CompanyPagesController extends Controller
{
    public function presentation(): View
    {
        return view('company.presentation', $this->viewData());
    }

    public function about(): View
    {
        return view('company.about', $this->viewData());
    }

    public function services(): View
    {
        return view('company.services', $this->viewData());
    }

    public function solutions(): View
    {
        return view('company.solutions', $this->viewData());
    }

    public function contact(): View
    {
        return view('company.contact', $this->viewData());
    }

    public function confidentialite(): View
    {
        return view('company.confidentialite', $this->viewData());
    }

    public function conditions(): View
    {
        return view('company.conditions', $this->viewData());
    }

    public function cookies(): View
    {
        return view('company.cookies', $this->viewData());
    }

    public function bureaux(): View
    {
        return view('company.bureaux', $this->viewData());
    }

    public function dmsPresentation(): View
    {
        return view('company.dms-presentation', $this->viewData());
    }

    public function contactRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\',\.0-9]+$/u'],
            'company_name' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\s\-\',\.0-9]+$/u'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'intent' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-\',\.0-9]+$/u'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'in:website,dms,contact'],
        ], [
            'name.regex' => 'Le nom ne peut contenir que des lettres, espaces et tirets.',
            'company_name.regex' => 'Le nom de l\'entreprise ne peut contenir que des lettres, espaces et tirets.',
            'email.email' => 'Veuillez fournir une adresse email valide.',
            'intent.regex' => 'L\'intention doit contenir des caractères valides.',
        ]);

        $source = $validated['source'] ?? 'website';

        ContactRequest::create([
            'name' => $validated['name'],
            'company_name' => $validated['company_name'] ?? null,
            'email' => $validated['email'],
            'intent' => $validated['intent'],
            'message' => $validated['message'] ?? null,
            'status' => 'new',
            'source' => $source,
        ]);

        $redirectTarget = match ($source) {
            'dms' => route('dms.presentation') . '/#contact',
            'contact' => route('company.contact'),
            default => route('company.presentation') . '/#contact',
        };

        return redirect()->to($redirectTarget)->with(
            'status',
            'Merci ' . e($validated['name']) . ' — votre demande a bien été reçue. Nous vous recontacterons rapidement.'
        );
    }

    /**
     * Resolve the active company for public pages.
     * For public pages there is no authenticated session, so we fall back to
     * the first active Company row.
     */
    protected function company(): ?Company
    {
        return Company::query()->where('is_active', true)->first();
    }

    /**
     * Build a standard view data array from the resolved Company.
     */
    protected function viewData(): array
    {
        $company = $this->company();
        $company->logo_path = "images/cm-logo.svg";

        return [
            'company' => $company,
            'companyName' => $company?->name ?: 'CROMMIX MALI S.A.',
            'companyLogoUrl' => "images/cm-logo.svg", //$company?->logo_path ? Storage::disk('public')->url($company->logo_path) : null,
            'companyEmail' => $company?->email ?: 'contact@crommix.com',
            'companyPhone' => $company?->phone ?: '',
            'companyAddress' => trim(collect([$company?->address, $company?->city, $company?->country])->filter()->implode(', ')),
            'companyWebsite' => $company?->website ?: '',
        ];
    }
}
