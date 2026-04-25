<?php

namespace Crommix\Blog\Http\Controllers;

use Crommix\Blog\Models\BlogPost;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PublicBlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->paginate(10);

        return view('crommix-blog::blog.index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('crommix-blog::blog.show', [
            'post' => $post,
        ]);
    }
}
