<?php

namespace Crommix\Blog\Support;

use App\Models\Company;
use Crommix\Blog\Models\BlogCategory;
use Illuminate\Database\Eloquent\Builder;

/** The "Labs" category: experiments and future products. */
final class Labs
{
    public const SLUG = 'labs';

    /** URL of the Labs page, or null when the blog is off or Labs has nothing published yet. */
    public static function url(?Company $company): ?string
    {
        if ($company === null || ! company_feature_enabled('blog', $company)) {
            return null;
        }

        $hasPosts = BlogCategory::forCompany($company->id)
            ->where('slug', self::SLUG)
            ->whereHas('posts', fn (Builder $query) => $query->withoutGlobalScope('company')->published())
            ->exists();

        return $hasPosts ? route('blog.category', self::SLUG) : null;
    }
}
