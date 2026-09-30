<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use App\Support\PublicSiteCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Fallback route: runs only when no other route matched. Serves the public
 * site's managed redirects, otherwise a normal 404.
 */
class RedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(404);
        }

        $company = PublicSiteCompany::resolve($request);
        $redirect = $company !== null ? Redirect::match($company->id, $request->path()) : null;

        if ($redirect === null) {
            abort(404);
        }

        $redirect->recordHit();

        return redirect()->away(
            $redirect->destinationFor($request->path(), $request->getQueryString()),
            $redirect->status_code,
        );
    }
}
