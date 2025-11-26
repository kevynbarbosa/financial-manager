<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Módulo de Usuários
            [
                'name' => 'view-users',
                'module' => 'users',
                'display_name' => 'Ver Usuários',
                'description' => 'Permite visualizar a lista de usuários',
            ],
            [
                'name' => 'create-users',
                'module' => 'users',
                'display_name' => 'Criar Usuários',
                'description' => 'Permite criar novos usuários',
            ],
            [
                'name' => 'edit-users',
                'module' => 'users',
                'display_name' => 'Editar Usuários',
                'description' => 'Permite editar usuários existentes',
            ],
            [
                'name' => 'delete-users',
                'module' => 'users',
                'display_name' => 'Deletar Usuários',
                'description' => 'Permite deletar usuários',
            ],
            // Módulo de Roles
            [
                'name' => 'view-roles',
                'module' => 'roles',
                'display_name' => 'Ver Roles',
                'description' => 'Permite visualizar a lista de roles',
            ],
            [
                'name' => 'create-roles',
                'module' => 'roles',
                'display_name' => 'Criar Roles',
                'description' => 'Permite criar novas roles',
            ],
            [
                'name' => 'edit-roles',
                'module' => 'roles',
                'display_name' => 'Editar Roles',
                'description' => 'Permite editar roles existentes',
            ],
            [
                'name' => 'delete-roles',
                'module' => 'roles',
                'display_name' => 'Deletar Roles',
                'description' => 'Permite deletar roles',
            ],
            // Módulo de Permissões
            [
                'name' => 'view-permissions',
                'module' => 'permissions',
                'display_name' => 'Ver Permissões',
                'description' => 'Permite visualizar a lista de permissões',
            ],
            [
                'name' => 'create-permissions',
                'module' => 'permissions',
                'display_name' => 'Criar Permissões',
                'description' => 'Permite criar novas permissões',
            ],
            [
                'name' => 'edit-permissions',
                'module' => 'permissions',
                'display_name' => 'Editar Permissões',
                'description' => 'Permite editar permissões existentes',
            ],
            [
                'name' => 'delete-permissions',
                'module' => 'permissions',
                'display_name' => 'Deletar Permissões',
                'description' => 'Permite deletar permissões',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
