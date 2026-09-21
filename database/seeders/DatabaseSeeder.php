<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['staff.invite', 'staff.revoke', 'programs.create', 'programs.review', 'profile.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        Role::findOrCreate('member', 'web')->syncPermissions([]);
        Role::findOrCreate('staff', 'web')->syncPermissions(['programs.create', 'profile.manage']);
        Role::findOrCreate('admin', 'web')->syncPermissions(['staff.invite', 'staff.revoke', 'programs.create', 'programs.review', 'profile.manage']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // Never seed a privileged user or a known password.
    }
}
