<?php

namespace Crommix\Core\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contract for models that are scoped to a company (tenant).
 * Implementing models must have a `company_id` column.
 */
interface HasTenantScope
{
    /**
     * Remove the company scope for a query, e.g. for super-admin contexts.
     *
     * @return Builder<static>
     */
    public static function withoutCompanyScope(): Builder;

    /**
     * Scope a query to a specific company, bypassing the global scope.
     *
     * @return Builder<static>
     */
    public static function forCompany(int $companyId): Builder;
}
