<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $roles = Role::withCount('users')
            ->when($search, function ($query, $search) {
                $query->where('display_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        $permissions = Permission::all();

        return Inertia::render('roles/Form', [
            'permissions' => $permissions,
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create($request->only(['display_name', 'description']));

        // Sync permissions if provided
        if ($request->has('permissions') && is_array($request->permissions)) {
            $permissionIds = $this->getPermissionIds($request->permissions);
            $role->permissions()->sync($permissionIds);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Perfil criado com sucesso.');
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions');
        $permissions = Permission::all();

        return Inertia::render('roles/Form', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->only(['display_name', 'description']));

        // Sync permissions if provided
        if ($request->has('permissions') && is_array($request->permissions)) {
            $permissionIds = $this->getPermissionIds($request->permissions);
            $role->permissions()->sync($permissionIds);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Perfil atualizado com sucesso.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'Não é possível deletar uma role que possui usuários atribuídos.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deletada com sucesso.');
    }

    public function permissions(Role $role): Response
    {
        $role->load('permissions');

        return Inertia::render('roles/Permissions', [
            'role' => $role,
            'permissions' => $role->permissions,
        ]);
    }

    private function getPermissionIds(array $permissionNames): array
    {
        return Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
    }
}
