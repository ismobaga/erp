<?php

namespace Crommix\Blog\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlogEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = Company::query()->where('is_active', true)->first();

        if (! company_feature_enabled('blog', $company)) {
            abort(404);
        }

        return $next($request);
    }
}
