<?php

namespace App\Filament\Concerns;

use App\Support\ErpEdition;
use Illuminate\Database\Eloquent\Model;

trait HasPermissionAccess
{
    protected static function isVisibleInCurrentEdition(): bool
    {
        if (! property_exists(static::class, 'hideInSimpleMode') || ! static::$hideInSimpleMode) {
            return true;
        }

        return ! ErpEdition::isSimple();
    }

    protected static function canAccessPermission(string $action): bool
    {
        if (! static::isVisibleInCurrentEdition() || ! static::isEditionFeatureEnabled()) {
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
        $permissionScope = property_exists(static::class, 'permissionScope') ? static::$permissionScope : null;

        return ErpEdition::isPermissionScopeEnabled($permissionScope);
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
