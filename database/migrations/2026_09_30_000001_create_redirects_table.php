<?php

use App\Support\CurrentCompanyTeamResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = ['redirects.view', 'redirects.create', 'redirects.update', 'redirects.delete'];

    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('source_path');
            $table->string('target_url', 2048);
            $table->unsignedSmallInteger('status_code')->default(302);
            $table->boolean('include_subpaths')->default(false);
            $table->boolean('preserve_query')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'source_path']);
        });

        $this->grantPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');

        if (Schema::hasTable('permissions')) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);

            try {
                Permission::query()->whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->delete();
            } finally {
                CurrentCompanyTeamResolver::clearOverride();
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }
    }

    /** Additive grant for existing installs (the seeder would re-sync every role). */
    private function grantPermissions(): void
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
                'Read Only' => ['redirects.view'],
            ];

            foreach ($grants as $roleName => $permissions) {
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
};
