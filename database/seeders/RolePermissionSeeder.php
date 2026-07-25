<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value);
        }

        $admin = RoleModel::findOrCreate(Role::ADMIN->value);
        $admin->syncPermissions(collect(Permission::cases())->map->value);

        $editor = RoleModel::findOrCreate(Role::EDITOR->value);
        $editor->syncPermissions([
            Permission::BLOGS_CREATE->value,
            Permission::BLOGS_VIEW_ANY->value,
            Permission::BLOGS_UPDATE_ANY->value,
            Permission::BLOGS_DELETE_ANY->value,
        ]);

        $author = RoleModel::findOrCreate(Role::AUTHOR->value);
        $author->syncPermissions([
            Permission::BLOGS_CREATE->value,
            Permission::BLOGS_VIEW_OWN->value,
            Permission::BLOGS_UPDATE_OWN->value,
            Permission::BLOGS_DELETE_OWN->value,
        ]);
    }
}
