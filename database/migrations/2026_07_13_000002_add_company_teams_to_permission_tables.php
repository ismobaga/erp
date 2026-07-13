<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrades the Spatie permission tables to the teams schema, where the "team"
 * is a company. Fresh databases already get this schema from the (config
 * driven) create_permission_tables migration, so everything here is guarded.
 *
 * Data backfill for existing assignments:
 *   - 'Super Admin' assignments become global (company_id = NULL).
 *   - Every other role assignment is replicated once per company the user
 *     belongs to (previously roles were global, so this preserves behavior).
 *   - Assignments for users without companies keep company_id = NULL.
 *
 * The pivot tables are rebuilt (drop + recreate) rather than altered in place
 * because their primary keys must be replaced, which SQLite cannot do in situ.
 */
return new class extends Migration
{
    public function up(): void
    {
        $teamsKey = config('permission.column_names.team_foreign_key', 'company_id');

        // Fresh install created with teams enabled — nothing to upgrade.
        if (Schema::hasColumn('model_has_roles', $teamsKey)) {
            return;
        }

        // ── roles: add the (nullable) team column ──────────────────────────
        // Role definitions stay global (company_id = NULL): the role catalog
        // is shared, only *assignments* are company-scoped. The original
        // unique(name, guard_name) is intentionally kept.
        if (! Schema::hasColumn('roles', $teamsKey)) {
            Schema::table('roles', function (Blueprint $table) use ($teamsKey): void {
                $table->unsignedBigInteger($teamsKey)->nullable()->after('id');
                $table->index($teamsKey, 'roles_team_foreign_key_index');
            });
        }

        // ── model_has_roles: rebuild with company scoping ──────────────────
        $assignments = DB::table('model_has_roles')->get();

        $superAdminRoleIds = DB::table('roles')->where('name', 'Super Admin')->pluck('id')->all();

        $companiesByUser = DB::table('company_user')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('company_id')->all());

        Schema::drop('model_has_roles');

        Schema::create('model_has_roles', function (Blueprint $table) use ($teamsKey): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();

            $table->unsignedBigInteger($teamsKey)->nullable();
            $table->index($teamsKey, 'model_has_roles_team_foreign_key_index');

            $table->unique([$teamsKey, 'role_id', 'model_id', 'model_type'],
                'model_has_roles_role_model_type_unique');
        });

        $rows = [];

        foreach ($assignments as $assignment) {
            $isUser = $assignment->model_type === \App\Models\User::class;
            $isSuperAdmin = in_array((int) $assignment->role_id, array_map('intval', $superAdminRoleIds), true);

            $companyIds = ($isUser && ! $isSuperAdmin)
                ? ($companiesByUser[$assignment->model_id] ?? [])
                : [];

            if ($companyIds === []) {
                // Super Admin (global) or a model without company memberships.
                $companyIds = [null];
            }

            foreach ($companyIds as $companyId) {
                $rows[] = [
                    'role_id' => $assignment->role_id,
                    'model_type' => $assignment->model_type,
                    'model_id' => $assignment->model_id,
                    $teamsKey => $companyId,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('model_has_roles')->insert($chunk);
        }

        // ── model_has_permissions: rebuild with company scoping ────────────
        $directPermissions = DB::table('model_has_permissions')->get();

        Schema::drop('model_has_permissions');

        Schema::create('model_has_permissions', function (Blueprint $table) use ($teamsKey): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();

            $table->unsignedBigInteger($teamsKey)->nullable();
            $table->index($teamsKey, 'model_has_permissions_team_foreign_key_index');

            $table->unique([$teamsKey, 'permission_id', 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_unique');
        });

        $permissionRows = [];

        foreach ($directPermissions as $assignment) {
            $companyIds = $assignment->model_type === \App\Models\User::class
                ? ($companiesByUser[$assignment->model_id] ?? [])
                : [];

            if ($companyIds === []) {
                $companyIds = [null];
            }

            foreach ($companyIds as $companyId) {
                $permissionRows[] = [
                    'permission_id' => $assignment->permission_id,
                    'model_type' => $assignment->model_type,
                    'model_id' => $assignment->model_id,
                    $teamsKey => $companyId,
                ];
            }
        }

        foreach (array_chunk($permissionRows, 500) as $chunk) {
            DB::table('model_has_permissions')->insert($chunk);
        }

        $this->clearPermissionCache();
    }

    public function down(): void
    {
        $teamsKey = config('permission.column_names.team_foreign_key', 'company_id');

        if (! Schema::hasColumn('model_has_roles', $teamsKey)) {
            return;
        }

        // Collapse per-company assignments back into global ones (dedupe).
        $roleRows = DB::table('model_has_roles')
            ->select('role_id', 'model_type', 'model_id')
            ->distinct()
            ->get();

        Schema::drop('model_has_roles');

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        foreach (array_chunk($roleRows->map(fn ($r) => (array) $r)->all(), 500) as $chunk) {
            DB::table('model_has_roles')->insert($chunk);
        }

        $permissionRows = DB::table('model_has_permissions')
            ->select('permission_id', 'model_type', 'model_id')
            ->distinct()
            ->get();

        Schema::drop('model_has_permissions');

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        foreach (array_chunk($permissionRows->map(fn ($r) => (array) $r)->all(), 500) as $chunk) {
            DB::table('model_has_permissions')->insert($chunk);
        }

        if (Schema::hasColumn('roles', $teamsKey)) {
            Schema::table('roles', function (Blueprint $table) use ($teamsKey): void {
                $table->dropIndex('roles_team_foreign_key_index');
                $table->dropColumn($teamsKey);
            });
        }

        $this->clearPermissionCache();
    }

    private function clearPermissionCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
