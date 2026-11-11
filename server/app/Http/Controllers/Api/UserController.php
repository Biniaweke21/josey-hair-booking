<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')->orderBy('name')->paginate(20);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json([
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $this->authorize('assignRole', $user);

        $user->syncRoles([$request->string('role')->toString()]);

        return response()->json([
            'message' => 'Role assigned successfully.',
            'data' => new UserResource($user->fresh()->load('roles')),
        ]);
    }
}
