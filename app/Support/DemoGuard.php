<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Validation\ValidationException;

class DemoGuard
{
    public static function ensureCompanyDeletionAllowed(Company $company): void
    {
        if (! config('demo.enabled') || ! config('demo.read_only') || ! $company->is_demo) {
            return;
        }

        throw ValidationException::withMessages([
            'company' => 'The demo company is protected and cannot be deleted while demo read-only mode is enabled.',
        ]);
    }

    public static function ensureDemoAdminDeletionAllowed(bool $isDemoAdmin): void
    {
        if (! config('demo.enabled') || ! config('demo.read_only') || ! $isDemoAdmin) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => 'The demo super admin is protected and cannot be deleted while demo read-only mode is enabled.',
        ]);
    }

    public static function ensureAccountingDeletionAllowed(int|string|null $companyId, string $resourceLabel): void
    {
        if (! config('demo.enabled') || ! config('demo.read_only') || blank($companyId)) {
            return;
        }

        $isDemoCompany = Company::query()
            ->whereKey($companyId)
            ->value('is_demo');

        if (! $isDemoCompany) {
            return;
        }

        throw ValidationException::withMessages([
            'demo' => sprintf('Demo read-only mode is enabled. Deleting %s records is not allowed.', $resourceLabel),
        ]);
    }
}
