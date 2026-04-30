<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCurrentCompany
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $companyId = session('current_company_id');

        $company = $companyId
            ? $user->companies()->where('companies.id', $companyId)->first()
            : null;

        if (! $company) {
            $company = $user->companies()->first();

            if ($company) {
                session(['current_company_id' => $company->id]);
            }
        }

        if ($company) {
            app()->instance('currentCompany', $company);
        }

        return $next($request);
    }
}
