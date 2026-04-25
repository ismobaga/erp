<?php

use Crommix\Blog\Http\Controllers\PublicBlogController;
use Crommix\Blog\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->group(function (): void {
    Route::get('/', [PublicBlogController::class, 'index'])->name('blog.index');
    Route::get('/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');
});

Route::get('/pages/{slug}', [PublicPageController::class, 'show'])->name('blog.pages.show');
