<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Crommix\SaaS\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Increments the `api_calls_daily` quota counter for the authenticated tenant
 * on every API request. If the quota is exceeded the request is rejected with
 * HTTP 429 (Too Many Requests).
 *
 * The middleware silently no-ops when the SaaS package tables are absent so
 * that the application works in bare-schema environments (e.g. during initial
 * setup or in test suites that have not run the SaaS migrations).
 */
class TrackApiQuota
{
    public function __construct(private readonly TenantManager $saas) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('tenant_usage_quotas')) {
            return $next($request);
        }

        /** @var Company|null $company */
        $company = app()->bound('currentCompany') ? app('currentCompany') : null;

        if ($company instanceof Company) {
            // Enforce quota before processing.
            if (! $this->saas->checkQuota('api_calls_daily', $company)) {
                return response()->json([
                    'message' => 'Daily API call quota exceeded.',
                    'metric' => 'api_calls_daily',
                ], 429);
            }

            // Increment usage after the check passes.
            $this->saas->incrementUsage('api_calls_daily', $company);
        }

        return $next($request);
    }
}
