<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminRoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get()->map(fn ($role) => [
            'roleSlug' => $role->role_slug,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Roles retrieved', $roles->all());
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Role retrieved', [
            'roleSlug' => $role->role_slug,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'role_slug' => (string) Str::uuid(),
            'name' => $data['name'],
        ]);

        $role->permissions()->sync($data['permissions']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Role created', [
            'roleSlug' => $role->role_slug,
            'name' => $role->name,
            'permissions' => $data['permissions'],
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        $role->load('permissions');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Role updated', [
            'roleSlug' => $role->role_slug,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Role deleted', []);
    }
}
