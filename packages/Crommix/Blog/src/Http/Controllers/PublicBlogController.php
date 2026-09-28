<?php

namespace Crommix\Blog\Http\Controllers;

use Crommix\Blog\Models\BlogCategory;
use Crommix\Blog\Models\BlogPost;
use Crommix\Blog\Models\BlogTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PublicBlogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        // The latest featured post heads the unfiltered listing and is left
        // out of the grid so it never appears twice.
        $featured = $search === ''
            ? $this->published()->where('is_featured', true)->first()
            : null;

        $posts = $this->listing(
            fn (Builder $query) => $query
                ->when($featured !== null, fn (Builder $query) => $query->whereKeyNot($featured->id))
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

                    $query->where(function (Builder $inner) use ($like): void {
                        $inner->where('title', 'like', $like)
                            ->orWhere('excerpt', 'like', $like)
                            ->orWhere('content', 'like', $like);
                    });
                })
        );

        if (! $posts->onFirstPage()) {
            $featured = null;
        }

        return view('crommix-blog::blog.index', [
            'posts' => $posts,
            'featured' => $featured,
            'search' => $search,
            'categories' => $this->categoriesWithPosts(),
            'heading' => 'Blog',
            'intro' => 'Articles opérationnels, retours terrain et stratégies de croissance pour structurer vos opérations.',
            'activeCategory' => null,
            'activeTag' => null,
        ]);
    }

    public function category(string $slug): View
    {
        $category = BlogCategory::query()->where('slug', $slug)->firstOrFail();

        return view('crommix-blog::blog.index', [
            'posts' => $this->listing(fn (Builder $query) => $query->where('category_id', $category->id)),
            'featured' => null,
            'search' => '',
            'categories' => $this->categoriesWithPosts(),
            'heading' => $category->name,
            'intro' => $category->description,
            'activeCategory' => $category,
            'activeTag' => null,
        ]);
    }

    public function tag(string $slug): View
    {
        $tag = BlogTag::query()->where('slug', $slug)->firstOrFail();

        return view('crommix-blog::blog.index', [
            'posts' => $this->listing(fn (Builder $query) => $query->whereHas('tags', fn (Builder $q) => $q->whereKey($tag->id))),
            'featured' => null,
            'search' => '',
            'categories' => $this->categoriesWithPosts(),
            'heading' => '#'.$tag->name,
            'intro' => null,
            'activeCategory' => null,
            'activeTag' => $tag,
        ]);
    }

    public function show(string $slug): View
    {
        $post = $this->published()
            ->with('tags')
            ->where('slug', $slug)
            ->firstOrFail();

        $related = $this->published()
            ->whereKeyNot($post->id)
            ->when(
                $post->category_id !== null,
                fn (Builder $query) => $query->where('category_id', $post->category_id),
            )
            ->limit(3)
            ->get();

        return view('crommix-blog::blog.show', [
            'post' => $post,
            'related' => $related,
        ]);
    }

    public function feed(): Response
    {
        $posts = $this->published()
            ->limit((int) config('crommix-blog.feed_limit', 20))
            ->get();

        return response()
            ->view('crommix-blog::blog.feed', ['posts' => $posts])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /** @return Builder<BlogPost> */
    protected function published(): Builder
    {
        return BlogPost::query()
            ->published()
            ->with(['author', 'category'])
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->latest('id');
    }

    protected function listing(callable $constraint): LengthAwarePaginator
    {
        return $constraint($this->published())
            ->paginate((int) config('crommix-blog.per_page', 9))
            ->withQueryString();
    }

    protected function categoriesWithPosts()
    {
        return BlogCategory::query()
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
