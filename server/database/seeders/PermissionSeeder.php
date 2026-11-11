<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Modules with full CRUD permissions.
     *
     * @var list<string>
     */
    private array $crudModules = [
        'categories',
        'services',
        'variation_types',
        'add_ons',
        'employees',
        'bookings',
        'customers',
        'gallery',
    ];

    public function run(): void
    {
        $permissions = [];

        foreach ($this->crudModules as $module) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        $permissions = array_merge($permissions, [
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'users.view',
            'users.manage-roles',
            'settings.view',
            'settings.update',
            'notifications.view',
            'notifications.manage',
            'reports.view',
        ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminRole->syncPermissions($permissions);
    }
}
