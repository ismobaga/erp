<?php

namespace Crommix\Blog\Http\Middleware;

use Closure;
use Crommix\Blog\Support\PublicBlogCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the company whose blog is served publicly, binds it as the
 * current company (so HasCompanyScope filters posts/pages/taxonomies to it)
 * and returns 404 when that company has the blog feature disabled.
 */
class CheckBlogEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = PublicBlogCompany::resolve($request);

        if ($company === null || ! company_feature_enabled('blog', $company)) {
            abort(404);
        }

        app()->instance('currentCompany', $company);
        View::share('blogCompany', $company);

        return $next($request);
    }
}
