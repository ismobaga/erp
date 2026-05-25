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

if (! function_exists('csp_nonce')) {
    /**
     * Return a per-request CSP nonce value.
     */
    function csp_nonce(): string
    {
        if (app()->bound('csp_nonce')) {
            return (string) app('csp_nonce');
        }

        try {
            $nonce = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to generate CSP nonce.', 0, $e);
        }

        app()->instance('csp_nonce', $nonce);

        return $nonce;
    }
}
