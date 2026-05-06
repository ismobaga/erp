<?php

namespace Crommix\Core\Contracts;

/**
 * Contract for models that carry permission-checked operations.
 */
interface HasPermissions
{
    /**
     * Returns the permission key used to authorize viewing this resource.
     */
    public static function viewPermission(): string;

    /**
     * Returns the permission key used to authorize creating this resource.
     */
    public static function createPermission(): string;

    /**
     * Returns the permission key used to authorize updating this resource.
     */
    public static function updatePermission(): string;

    /**
     * Returns the permission key used to authorize deleting this resource.
     */
    public static function deletePermission(): string;
}
