<?php

use Crommix\Blog\Http\Controllers\PublicBlogController;
use Crommix\Blog\Http\Controllers\PublicPageController;
use Crommix\Blog\Http\Controllers\SitemapController;
use Crommix\Blog\Http\Middleware\CheckBlogEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', CheckBlogEnabled::class])->group(function (): void {
    Route::prefix('blog')->group(function (): void {
        Route::get('/', [PublicBlogController::class, 'index'])->name('blog.index');
        Route::get('/feed.xml', [PublicBlogController::class, 'feed'])->name('blog.feed');
        Route::get('/categorie/{slug}', [PublicBlogController::class, 'category'])->name('blog.category');
        Route::get('/tag/{slug}', [PublicBlogController::class, 'tag'])->name('blog.tag');
        Route::get('/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');
    });

    Route::get('/pages/{slug}', [PublicPageController::class, 'show'])->name('blog.pages.show');
});

// Site-wide sitemap: marketing pages always, blog content when the blog is enabled.
Route::get('/sitemap.xml', SitemapController::class)
    ->middleware(['web', 'throttle:60,1'])
    ->name('sitemap');
