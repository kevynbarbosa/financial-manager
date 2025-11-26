<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['display_name' => 'Administrador'],
            [
                'description' => 'Perfil com acesso total ao sistema',
            ]
        );

        $managerRole = Role::firstOrCreate(
            ['display_name' => 'Gerente'],
            [
                'description' => 'Perfil com acesso de gerenciamento',
            ]
        );

        $userRole = Role::firstOrCreate(
            ['display_name' => 'Usuário'],
            [
                'description' => 'Perfil básico de usuário',
            ]
        );

        $adminPermissions = Permission::all();
        $adminRole->permissions()->sync($adminPermissions->pluck('id'));

        $managerPermissions = Permission::whereIn('name', [
            'view-users',
            'create-users',
            'edit-users',
            'view-roles',
            'view-permissions',
        ])->get();
        $managerRole->permissions()->sync($managerPermissions->pluck('id'));

        $userPermissions = Permission::whereIn('name', [
            'view-users',
        ])->get();
        $userRole->permissions()->sync($userPermissions->pluck('id'));
    }
}
