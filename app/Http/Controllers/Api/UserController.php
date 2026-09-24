<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $page = User::query()->orderBy('name')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($page->getCollection())->resolve(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $user = User::create($request->validated());
        $auditLogger->record($request->user(), 'user.created', $user, null, $user->only(['name', 'email', 'role', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => (new UserResource($user))->resolve(),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json(['success' => true, 'data' => (new UserResource($user))->resolve()]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validated();
        if ($request->user()->is($user) && array_key_exists('is_active', $validated) && ! $validated['is_active']) {
            throw ValidationException::withMessages(['is_active' => ['You cannot deactivate your own account.']]);
        }
        $removesAdministratorAccess = $user->isAdmin()
            && (($validated['role'] ?? User::ROLE_ADMIN) !== User::ROLE_ADMIN || ($validated['is_active'] ?? true) === false);
        if ($removesAdministratorAccess && User::query()->where('role', User::ROLE_ADMIN)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['role' => ['At least one active administrator is required.']]);
        }
        $before = $user->only(['name', 'email', 'role', 'is_active']);
        $user->update($validated);
        $auditLogger->record($request->user(), 'user.updated', $user, $before, $user->only(['name', 'email', 'role', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => (new UserResource($user))->resolve(),
        ]);
    }
}
