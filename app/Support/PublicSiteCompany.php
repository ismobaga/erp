<?php

namespace App\Support;

use App\Models\Company;
use Crommix\Blog\Support\PublicBlogCompany;
use Illuminate\Http\Request;

/** The company whose public website is being served for this request. */
final class PublicSiteCompany
{
    public static function resolve(?Request $request = null): ?Company
    {
        if (class_exists(PublicBlogCompany::class)) {
            return PublicBlogCompany::resolve($request);
        }

        return Company::query()->where('is_active', true)->orderBy('id')->first();
    }
}
