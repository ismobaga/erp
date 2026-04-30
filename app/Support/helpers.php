<?php

use App\Models\Company;

if (! function_exists('currentCompany')) {
    function currentCompany(): ?Company
    {
        return app()->bound('currentCompany')
            ? app('currentCompany')
            : null;
    }
}
