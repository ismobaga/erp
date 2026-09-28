<?php

namespace Crommix\Blog\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use Crommix\Blog\Support\BlogContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

class BlogPage extends Model
{
    use HasCompanyScope;

    protected $table = 'blog_pages';

    protected $fillable = [
        'company_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function renderedContent(): HtmlString
    {
        return BlogContent::toHtml($this->content);
    }
}
