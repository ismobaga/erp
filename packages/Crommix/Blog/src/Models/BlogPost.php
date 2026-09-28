<?php

namespace Crommix\Blog\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use App\Models\User;
use Crommix\Blog\Support\BlogContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasCompanyScope;

    protected $table = 'blog_posts';

    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'excerpt',
        'cover_image_path',
        'cover_image_alt',
        'content',
        'status',
        'is_featured',
        'published_at',
        'author_id',
        'category_id',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag', 'blog_post_id', 'blog_tag_id');
    }

    public function renderedContent(): HtmlString
    {
        return BlogContent::toHtml($this->content);
    }

    public function readingMinutes(): int
    {
        return BlogContent::readingMinutes($this->content);
    }

    public function coverImageUrl(): ?string
    {
        if (blank($this->cover_image_path)) {
            return null;
        }

        return Storage::disk(BlogContent::disk())->url($this->cover_image_path);
    }

    /** Excerpt if set, otherwise the start of the article as plain text. */
    public function summary(int $limit = 180): string
    {
        return filled($this->excerpt)
            ? (string) $this->excerpt
            : Str::limit(BlogContent::toText($this->content), $limit);
    }

    /** Date shown publicly: the scheduled date, or creation date when published without one. */
    public function publicDate(): ?Carbon
    {
        return $this->published_at ?? $this->created_at;
    }
}
