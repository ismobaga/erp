<?php

namespace App\Filament\Concerns;

use App\Support\ErpEdition;
use Illuminate\Database\Eloquent\Model;

trait HasPermissionAccess
{
    protected static function hidesInSimpleMode(): bool
    {
        static $cache = [];

        return $cache[static::class] ??= (function (): bool {
            if (! property_exists(static::class, 'hideInSimpleMode')) {
                return false;
            }

            $property = new \ReflectionProperty(static::class, 'hideInSimpleMode');

            return $property->isStatic() && (bool) $property->getValue(null);
        })();
    }

    protected static function isVisibleInCurrentEdition(): bool
    {
        if (filled(static::companyFeatureKey())) {
            return true;
        }

        return ! (static::hidesInSimpleMode() && ErpEdition::isSimple());
    }

    protected static function canAccessPermission(string $action): bool
    {
        if (! static::isVisibleInCurrentEdition() || ! static::isEditionFeatureEnabled() || ! static::isCompanyFeatureEnabled()) {
            return false;
        }

        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        $permissionScope = property_exists(static::class, 'permissionScope') ? static::$permissionScope : null;

        if (blank($permissionScope)) {
            return false;
        }

        return $user->can($permissionScope . '.' . $action);
    }

    protected static function isEditionFeatureEnabled(): bool
    {
        if (filled(static::companyFeatureKey())) {
            return true;
        }

        $permissionScope = property_exists(static::class, 'permissionScope') ? static::$permissionScope : null;

        return ErpEdition::isPermissionScopeEnabled($permissionScope);
    }

    protected static function isCompanyFeatureEnabled(): bool
    {
        $feature = static::companyFeatureKey();

        if (blank($feature)) {
            return true;
        }

        return company_feature_enabled((string) $feature);
    }

    protected static function companyFeatureKey(): ?string
    {
        if (! property_exists(static::class, 'companyFeature')) {
            return null;
        }

        $property = new \ReflectionProperty(static::class, 'companyFeature');

        if (! $property->isStatic()) {
            return null;
        }

        return $property->getValue();
    }

    public static function canViewAny(): bool
    {
        return static::isVisibleInCurrentEdition() && static::canAccessPermission('view');
    }

    public static function canView(Model $record): bool
    {
        return static::isVisibleInCurrentEdition() && static::canAccessPermission('view');
    }

    public static function canCreate(): bool
    {
        return static::isVisibleInCurrentEdition() && static::canAccessPermission('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::isVisibleInCurrentEdition() && static::canAccessPermission('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::isVisibleInCurrentEdition() && static::canAccessPermission('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::isVisibleInCurrentEdition() && static::canAccessPermission('delete');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && static::isVisibleInCurrentEdition() && static::canViewAny();
    }

    public static function canAccess(): bool
    {
        return static::isVisibleInCurrentEdition() && static::canAccessPermission('view');
    }
}
