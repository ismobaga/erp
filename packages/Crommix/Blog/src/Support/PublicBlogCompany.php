<?php

namespace Crommix\Blog\Support;

use App\Models\Company;
use Illuminate\Http\Request;

/**
 * Decides which company's blog the public site shows.
 *
 * Resolution order:
 *   1. The company whose slug is configured in crommix-blog.public_company
 *      (env BLOG_PUBLIC_COMPANY).
 *   2. The active company whose website host matches the request host.
 *   3. The first active company (previous single-company behaviour).
 */
final class PublicBlogCompany
{
    public static function resolve(?Request $request = null): ?Company
    {
        $active = Company::query()->where('is_active', true);

        $slug = config('crommix-blog.public_company');

        if (filled($slug)) {
            return (clone $active)->where('slug', $slug)->first();
        }

        $host = $request?->getHost();

        if (filled($host)) {
            $match = (clone $active)
                ->whereNotNull('website')
                ->get(['id', 'website'])
                ->first(fn (Company $company): bool => self::hostOf($company->website) === self::hostOf($host));

            if ($match !== null) {
                return Company::query()->find($match->id);
            }
        }

        return $active->orderBy('id')->first();
    }

    private static function hostOf(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = str_contains($url, '://') ? $url : 'https://'.$url;
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return null;
        }

        return strtolower((string) preg_replace('/^www\./i', '', $host));
    }
}
