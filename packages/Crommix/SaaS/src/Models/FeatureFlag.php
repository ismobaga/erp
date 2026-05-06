<?php

namespace Crommix\SaaS\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-tenant feature flag override.
 * Allows enabling or disabling a feature for a specific company regardless
 * of what the plan includes.
 *
 * @property int    $id
 * @property int    $company_id
 * @property string $feature
 * @property bool   $enabled
 * @property array|null $metadata
 */
class FeatureFlag extends Model
{
    protected $table = 'tenant_feature_flags';

    protected $fillable = [
        'company_id',
        'feature',
        'enabled',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'enabled'  => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
