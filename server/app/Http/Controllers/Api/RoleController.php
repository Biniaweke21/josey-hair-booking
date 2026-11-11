<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();

        return response()->json([
            'data' => RoleResource::collection($roles),
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create([
            'name' => $request->string('name')->toString(),
            'guard_name' => 'sanctum',
        ]);
        $role->syncPermissions($request->input('permissions', []));

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => new RoleResource($role->load('permissions')),
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return response()->json([
            'data' => new RoleResource($role->load('permissions')->loadCount('users')),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        if ($request->filled('name')) {
            $role->update(['name' => $request->string('name')->toString()]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions', []));
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => new RoleResource($role->fresh()->load('permissions')),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a role that is still assigned to users.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
