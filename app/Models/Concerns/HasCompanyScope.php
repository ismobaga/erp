<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\GlobalScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Automatically scopes all queries and new records to the current company.
 *
 * Usage: add `use HasCompanyScope;` to any Eloquent model whose table
 * contains a `company_id` foreign key referencing `companies`.
 */
trait HasCompanyScope
{
    protected static function bootHasCompanyScope(): void
    {
        // Apply a global scope so every SELECT is filtered by the current company.
        static::addGlobalScope('company', function (Builder $query): void {
            if (app()->bound('currentCompany')) {
                $query->where(
                    $query->getModel()->getTable() . '.company_id',
                    app('currentCompany')->id,
                );

                return;
            }

            if (!app()->runningInConsole()) {
                return;
            }

            $modelClass = $query->getModel()::class;
            $strict = (bool) config('erp.tenancy.require_company_context_in_console', false);
            $logMissing = (bool) config('erp.tenancy.log_missing_console_context', true);

            if ($strict) {
                throw new RuntimeException(sprintf(
                    'Missing currentCompany binding while querying tenant-scoped model %s in console context.',
                    $modelClass,
                ));
            }

            if ($logMissing) {
                static $warnedModels = [];

                if (!isset($warnedModels[$modelClass])) {
                    Log::warning('Tenant query executed without company context in console mode.', [
                        'model' => $modelClass,
                        'table' => $query->getModel()->getTable(),
                    ]);

                    $warnedModels[$modelClass] = true;
                }
            }
        });

        // Automatically assign company_id on creation.
        static::creating(function (Model $model): void {
            if (empty($model->company_id) && app()->bound('currentCompany')) {
                $model->company_id = app('currentCompany')->id;
            }
        });
    }

    /**
     * Remove the company scope for a query, e.g. for super-admin contexts.
     *
     * @return Builder<static>
     */
    public static function withoutCompanyScope(): Builder
    {
        return static::withoutGlobalScope('company');
    }

    /**
     * Scope a query to a specific company, bypassing the global scope.
     *
     * @return Builder<static>
     */
    public static function forCompany(int $companyId): Builder
    {
        return static::withoutGlobalScope('company')->where('company_id', $companyId);
    }
}
