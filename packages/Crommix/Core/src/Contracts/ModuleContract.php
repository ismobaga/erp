<?php

namespace Crommix\Core\Contracts;

/**
 * Contract for all ERP modules/packages.
 * Each module must be able to report whether it is enabled and provide its permissions.
 */
interface ModuleContract
{
    /**
     * Returns whether the module is currently enabled.
     */
    public static function isEnabled(): bool;

    /**
     * Returns the list of permissions this module registers.
     *
     * @return string[]
     */
    public static function permissions(): array;

    /**
     * Returns the module identifier used in configuration and routes.
     */
    public static function moduleKey(): string;
}
