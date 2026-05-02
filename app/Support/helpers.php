<?php

use App\Models\Company;

if (! function_exists('currentCompany')) {
    /**
     * Return the Company instance that is currently active in this request.
     *
     * The company is resolved from the IoC container, where it is bound by the
     * SetCurrentCompany middleware.  Returns null when no company is bound
     * (e.g. during CLI commands or unauthenticated requests).
     */
    function currentCompany(): ?Company
    {
        if (app()->bound('currentCompany')) {
            return app('currentCompany');
        }

        return null;
    }
}
