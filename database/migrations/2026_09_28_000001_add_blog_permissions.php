<?php

use App\Support\CurrentCompanyTeamResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Adds the blog permissions to existing installs without touching any other
 * role customisation (RolesAndPermissionsSeeder would re-sync every role).
 */
return new class extends Migration
{
    private const PERMISSIONS = ['blog.view', 'blog.create', 'blog.update', 'blog.delete', 'blog.publish'];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(null);

        try {
            foreach (self::PERMISSIONS as $name) {
                Permission::findOrCreate($name, 'web');
            }

            $grants = [
                'Super Admin' => self::PERMISSIONS,
                'Admin' => self::PERMISSIONS,
                'Editor' => ['blog.view', 'blog.create', 'blog.update'],
                'Read Only' => ['blog.view'],
            ];

            foreach ($grants as $roleName => $permissions) {
                if ($roleName === 'Editor') {
                    Role::findOrCreate($roleName, 'web');
                }

                // Extend every existing role with that name (the global one and
                // any per-company copy); other roles are left untouched.
                Role::query()
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->get()
                    ->each(fn (Role $role) => $role->givePermissionTo($permissions));
            }
        } finally {
            CurrentCompanyTeamResolver::clearOverride();
            $registrar->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        try {
            Permission::query()->whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->delete();
            Role::query()->where('name', 'Editor')->where('guard_name', 'web')->delete();
        } finally {
            CurrentCompanyTeamResolver::clearOverride();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
