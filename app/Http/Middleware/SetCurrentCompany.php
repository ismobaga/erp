<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active company for the current request and binds it to the
 * IoC container as 'currentCompany'.
 *
 * Resolution order:
 *   1. The company_id stored in the session (set by the company switcher).
 *   2. The first company the authenticated user belongs to.
 *
 * When no company can be resolved (e.g. the user has no company yet), nothing
 * is bound so the HasCompanyScope global scope simply returns all records —
 * graceful degradation rather than a hard failure.
 */
class SetCurrentCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $companyId = session('current_company_id');

        $company = $companyId
            ? $user->companies()->find($companyId)
            : $user->companies()->first();

        if ($company instanceof Company) {
            app()->instance('currentCompany', $company);
            // Keep session in sync with the resolved company.
            session(['current_company_id' => $company->id]);
        }


        return $next($request);
    }
}
