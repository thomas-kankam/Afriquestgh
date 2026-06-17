<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::query()->get()->map->toApiArray();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permissions retrieved', $permissions->all());
    }

    public function show(Permission $permission): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permission retrieved', $permission->toApiArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'label' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create($data);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Permission created', $permission->toApiArray());
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:permissions,name,'.$permission->id,
            'label' => 'nullable|string|max:255',
        ]);

        $permission->update($data);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permission updated', $permission->fresh()->toApiArray());
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permission->roles()->detach();
        $permission->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permission deleted', []);
    }
}
