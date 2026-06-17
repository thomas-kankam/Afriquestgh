<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class AdminPermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::query()->get(['name', 'label']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permissions retrieved', $permissions->all());
    }

    public function show(Permission $permission): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Permission retrieved', $permission->only(['name', 'label']));
    }
}
