<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Resolves the Spatie "team" id (our company id) for permission checks.
 *
 * Resolution order:
 *   1. An explicit override set via setPermissionsTeamId() — used by seeders,
 *      migrations, and User::isSuperAdmin() to force a specific (or global)
 *      context.
 *   2. The currentCompany container binding (normal web/API flow).
 *   3. The authenticated user's session company (validated for membership),
 *      then their first attached company. This covers the login flow, where
 *      Filament checks canAccessPanel() before SetCurrentCompany has bound a
 *      company for the freshly authenticated user.
 *
 * State is static because Spatie's PermissionRegistrar instantiates the
 * resolver itself (outside the container), so different instances must share
 * the override.
 */
class CurrentCompanyTeamResolver implements PermissionsTeamResolver
{
    protected static int|string|null $overrideTeamId = null;

    protected static bool $overridden = false;

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        static::$overrideTeamId = $id;
        static::$overridden = true;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        if (static::$overridden) {
            return static::$overrideTeamId;
        }

        $company = currentCompany();

        if ($company !== null) {
            return $company->getKey();
        }

        return $this->resolveFromAuthenticatedUser();
    }

    /**
     * Remove any explicit override so resolution follows the current company
     * binding again. Call this after temporarily switching team context.
     */
    public static function clearOverride(): void
    {
        static::$overrideTeamId = null;
        static::$overridden = false;
    }

    protected function resolveFromAuthenticatedUser(): int|string|null
    {
        $user = Auth::user();

        if ($user === null || ! method_exists($user, 'companies')) {
            return null;
        }

        if (app()->bound('session')) {
            $sessionCompanyId = session('current_company_id');

            if (filled($sessionCompanyId) && $user->companies()->whereKey($sessionCompanyId)->exists()) {
                return $sessionCompanyId;
            }
        }

        return $user->companies()->value('companies.id');
    }
}
