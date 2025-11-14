<?php

namespace App\Providers;

use App\Models\Permission;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        // 'App\Models\Post' => 'App\Policies\PostPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerDynamicGates();
    }

    /**
     * Register dynamic gates based on permissions in database.
     */
    protected function registerDynamicGates(): void
    {
        try {
            // Verifica se as tabelas existem antes de tentar registrar os gates
            if (!app()->environment('testing') && \Schema::hasTable('permissions')) {
                $permissions = Permission::all();

                foreach ($permissions as $permission) {
                    Gate::define($permission->name, function ($user) use ($permission) {
                        return $user->hasPermission($permission->name);
                    });
                }
            }
        } catch (\Exception $e) {
            // Em caso de erro (ex: banco não existe ainda), apenas ignora
            // Isso evita erros durante migrações ou instalação inicial
        }

        // Gates especiais que sempre devem existir
        Gate::define('access-admin', function ($user) {
            return $user->hasRole('Admin') || $user->hasRole('Super Admin');
        });

        Gate::before(function ($user, $ability) {
            // Super Admin tem acesso a tudo
            if ($user->hasRole('Super Admin')) {
                return true;
            }
        });
    }
}