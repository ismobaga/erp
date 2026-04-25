<?php

namespace Crommix\Blog\Http\Controllers;

use Crommix\Blog\Models\BlogPage;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = BlogPage::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('crommix-blog::pages.show', [
            'page' => $page,
        ]);
    }
}
