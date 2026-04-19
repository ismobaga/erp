<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasPermissionAccess
{
    protected static function canAccessPermission(string $action): bool
    {
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

    public static function canViewAny(): bool
    {
        return static::canAccessPermission('view');
    }

    public static function canCreate(): bool
    {
        return static::canAccessPermission('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccessPermission('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccessPermission('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::canAccessPermission('delete');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && static::canViewAny();
    }

    public static function canAccess(): bool
    {
        return static::canAccessPermission('view');
    }
}
