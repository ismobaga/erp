<?php

namespace Crommix\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BlogPage extends Model
{
    protected $table = 'blog_pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'published_at',
        'template',
        'hero_title',
        'hero_subtitle',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $inner): void {
                $inner->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
