<?php

namespace App\Support;

class ErpEdition
{
    public static function current(): string
    {
        return (string) config('erp.edition.active', 'full');
    }

    public static function isModuleEnabled(string $module): bool
    {
        $module = trim(strtolower($module));

        if ($module === '') {
            return true;
        }

        $enabled = self::enabledModules();

        return in_array('*', $enabled, true) || in_array($module, $enabled, true);
    }

    public static function isPermissionScopeEnabled(?string $scope): bool
    {
        if (blank($scope)) {
            return true;
        }

        $module = self::moduleForPermissionScope((string) $scope);

        return self::isModuleEnabled($module);
    }

    public static function enabledModules(): array
    {
        $edition = self::current();

        $profileModules = (array) config("erp.edition.profiles.{$edition}.enabled_modules", []);

        if ($profileModules !== []) {
            return array_values(array_unique(array_map(static fn(string $module): string => trim(strtolower($module)), $profileModules)));
        }

        return ['*'];
    }

    public static function moduleForPermissionScope(string $scope): string
    {
        $scope = trim(strtolower($scope));

        $scopeMap = (array) config('erp.edition.scope_to_module', []);

        return (string) ($scopeMap[$scope] ?? $scope);
    }
}
