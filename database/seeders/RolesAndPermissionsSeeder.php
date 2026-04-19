<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
            'services.view',
            'services.create',
            'services.update',
            'services.delete',
            'quotes.view',
            'quotes.create',
            'quotes.update',
            'quotes.delete',
            'quotes.convert',
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.delete',
            'invoices.record_payment',
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',
            'expenses.view',
            'expenses.create',
            'expenses.update',
            'expenses.delete',
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'settings.view',
            'settings.update',
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $admin = Role::findOrCreate('Admin', 'web');
        $finance = Role::findOrCreate('Finance', 'web');
        $projectManager = Role::findOrCreate('Project Manager', 'web');
        $staff = Role::findOrCreate('Staff', 'web');
        $readOnly = Role::findOrCreate('Read Only', 'web');

        $superAdmin->syncPermissions(Permission::all());

        $admin->syncPermissions(Permission::whereNotIn('name', ['users.delete'])->get());

        $finance->syncPermissions(Permission::whereIn('name', [
            'clients.view',
            'quotes.view',
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.record_payment',
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',
            'expenses.view',
            'expenses.create',
            'expenses.update',
            'expenses.delete',
            'reports.view',
        ])->get());

        $projectManager->syncPermissions(Permission::whereIn('name', [
            'clients.view',
            'services.view',
            'quotes.view',
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'reports.view',
        ])->get());

        $staff->syncPermissions(Permission::whereIn('name', [
            'clients.view',
            'quotes.view',
            'projects.view',
        ])->get());

        $readOnly->syncPermissions(Permission::where('name', 'like', '%.view')->orWhere('name', 'reports.view')->get());
    }
}
