<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * A public short link, e.g. crommixmali.com/ai → https://ai.crommixmali.com.
 *
 * Redirects are only consulted when no real route matches (see the fallback
 * route), so they can never shadow an existing page.
 */
class Redirect extends Model
{
    use HasCompanyScope;

    public const STATUS_CODES = [
        302 => 'Temporaire (302)',
        301 => 'Permanente (301)',
    ];

    protected $fillable = [
        'company_id',
        'source_path',
        'target_url',
        'status_code',
        'include_subpaths',
        'preserve_query',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'include_subpaths' => 'boolean',
            'preserve_query' => 'boolean',
            'is_active' => 'boolean',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $redirect): void {
            $redirect->source_path = self::normalizePath((string) $redirect->source_path);
            $redirect->target_url = trim((string) $redirect->target_url);
        });

        static::saved(fn (self $redirect) => self::forgetCache($redirect->company_id));
        static::deleted(fn (self $redirect) => self::forgetCache($redirect->company_id));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** "AI/", "/ai", "ai" → "/ai". */
    public static function normalizePath(string $path): string
    {
        $path = strtolower(trim(rawurldecode($path)));
        $path = (string) preg_replace('#/+#', '/', '/'.trim($path, '/'));

        return $path;
    }

    /**
     * Find the redirect for a path: exact match first, otherwise the longest
     * "include sub-paths" prefix (/ai/docs matches /ai).
     */
    public static function match(int $companyId, string $path): ?self
    {
        $path = self::normalizePath($path);
        $redirects = self::activeFor($companyId);

        $exact = $redirects->firstWhere('source_path', $path);

        if ($exact !== null) {
            return $exact;
        }

        return $redirects
            ->filter(fn (self $redirect): bool => $redirect->include_subpaths
                && $redirect->source_path !== '/'
                && str_starts_with($path, $redirect->source_path.'/'))
            ->sortByDesc(fn (self $redirect): int => strlen($redirect->source_path))
            ->first();
    }

    /** Build the destination URL for a request path and query string. */
    public function destinationFor(string $path, ?string $queryString): string
    {
        $target = $this->target_url;
        $path = self::normalizePath($path);

        if ($this->include_subpaths && str_starts_with($path, $this->source_path.'/')) {
            $suffix = substr($path, strlen($this->source_path));
            $parts = parse_url($target);
            $base = strtok($target, '?#');
            $target = rtrim((string) $base, '/').$suffix
                .(isset($parts['query']) ? '?'.$parts['query'] : '')
                .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
        }

        if ($this->preserve_query && filled($queryString)) {
            [$withoutFragment, $fragment] = array_pad(explode('#', $target, 2), 2, null);
            $target = $withoutFragment.(str_contains($withoutFragment, '?') ? '&' : '?').$queryString
                .($fragment !== null ? '#'.$fragment : '');
        }

        return $target;
    }

    public function recordHit(): void
    {
        // Query-builder update: no model events, no updated_at bump.
        static::withoutCompanyScope()->whereKey($this->getKey())->toBase()->update([
            'hits' => DB::raw('hits + 1'),
            'last_hit_at' => now(),
        ]);
    }

    /** @return Collection<int, self> */
    protected static function activeFor(int $companyId): Collection
    {
        return Cache::remember(self::cacheKey($companyId), now()->addHour(), fn (): Collection => static::forCompany($companyId)
            ->where('is_active', true)
            ->get());
    }

    protected static function forgetCache(?int $companyId): void
    {
        if ($companyId !== null) {
            Cache::forget(self::cacheKey($companyId));
        }
    }

    protected static function cacheKey(int $companyId): string
    {
        return 'redirects:active:'.$companyId;
    }
}
