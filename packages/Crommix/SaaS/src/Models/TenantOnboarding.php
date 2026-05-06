<?php

namespace Crommix\SaaS\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tracks the tenant's onboarding workflow progress.
 *
 * @property int         $id
 * @property int         $company_id
 * @property array       $completed_steps
 * @property bool        $is_complete
 * @property Carbon|null $completed_at
 * @property array|null  $metadata
 */
class TenantOnboarding extends Model
{
    protected $table = 'tenant_onboarding';

    protected $fillable = [
        'company_id',
        'completed_steps',
        'is_complete',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'completed_steps' => 'array',
            'is_complete'     => 'boolean',
            'completed_at'    => 'datetime',
            'metadata'        => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Step management ────────────────────────────────────────────────────────

    /**
     * Whether the given step has been completed.
     */
    public function hasStep(string $step): bool
    {
        return in_array($step, (array) ($this->completed_steps ?? []), true);
    }

    /**
     * Mark a step as complete, and mark the whole onboarding as complete
     * when all configured steps are done.
     */
    public function completeStep(string $step): static
    {
        $steps = (array) ($this->completed_steps ?? []);

        if (!in_array($step, $steps, true)) {
            $steps[] = $step;
            $this->completed_steps = $steps;
        }

        $allSteps = array_keys((array) config('saas.onboarding_steps', []));

        if ($allSteps !== [] && empty(array_diff($allSteps, $steps))) {
            $this->is_complete  = true;
            $this->completed_at = $this->completed_at ?? now();
        }

        $this->save();

        return $this;
    }

    /**
     * Return all configured step keys not yet completed.
     *
     * @return string[]
     */
    public function pendingSteps(): array
    {
        $allSteps = array_keys((array) config('saas.onboarding_steps', []));

        return array_values(array_diff($allSteps, (array) ($this->completed_steps ?? [])));
    }

    /**
     * Overall progress as a percentage (0–100).
     */
    public function progressPercent(): float
    {
        $allSteps = array_keys((array) config('saas.onboarding_steps', []));

        if ($allSteps === []) {
            return 100.0;
        }

        $completed = count(array_intersect($allSteps, (array) ($this->completed_steps ?? [])));

        return round(($completed / count($allSteps)) * 100, 1);
    }
}
