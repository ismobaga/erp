<?php

namespace Crommix\Blog\Http\Controllers;

use Crommix\Blog\Models\BlogCategory;
use Crommix\Blog\Models\BlogPage;
use Crommix\Blog\Models\BlogPost;
use Crommix\Blog\Support\PublicBlogCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    /** Public marketing routes always listed in the sitemap. */
    protected const STATIC_ROUTES = [
        'company.presentation',
        'company.about',
        'company.services',
        'company.solutions',
        'company.contact',
        'company.bureaux',
        'company.confidentialite',
        'company.conditions',
        'company.cookies',
    ];

    public function __invoke(Request $request): Response
    {
        $urls = collect(self::STATIC_ROUTES)
            ->filter(fn (string $name): bool => Route::has($name))
            ->map(fn (string $name): array => ['loc' => route($name), 'lastmod' => null]);

        $company = PublicBlogCompany::resolve($request);

        if ($company !== null && company_feature_enabled('blog', $company)) {
            app()->instance('currentCompany', $company);
            $urls = $urls->merge($this->blogUrls());
        }

        return response()
            ->view('crommix-blog::sitemap', ['urls' => $urls->values()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** @return Collection<int, array{loc: string, lastmod: ?\DateTimeInterface}> */
    protected function blogUrls(): Collection
    {
        $posts = BlogPost::query()->published()->latest('updated_at')->get(['id', 'slug', 'updated_at']);

        $urls = collect([['loc' => route('blog.index'), 'lastmod' => $posts->first()?->updated_at]]);

        $urls = $urls->merge($posts->map(fn (BlogPost $post): array => [
            'loc' => route('blog.show', $post->slug),
            'lastmod' => $post->updated_at,
        ]));

        $urls = $urls->merge(
            BlogCategory::query()
                ->whereHas('posts', fn (Builder $query) => $query->published())
                ->get(['id', 'slug', 'updated_at'])
                ->map(fn (BlogCategory $category): array => [
                    'loc' => route('blog.category', $category->slug),
                    'lastmod' => null,
                ])
        );

        return $urls->merge(
            BlogPage::query()->published()->get(['id', 'slug', 'updated_at'])
                ->map(fn (BlogPage $page): array => [
                    'loc' => route('blog.pages.show', $page->slug),
                    'lastmod' => $page->updated_at,
                ])
        );
    }
}
