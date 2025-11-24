<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    /**
     * UserController constructor.
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        if ($search) {
            $users = $this->userService->searchUsers($search, $perPage);
        } else {
            $users = $this->userService->getAllUsers($perPage);
        }

        return $this->success($users, 'Users retrieved successfully');
    }

    /**
     * Store a newly created user.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return $this->success(
            new UserResource($user),
            'User created successfully',
            201
        );
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        return $this->success(
            new UserResource($user),
            'User retrieved successfully'
        );
    }

    /**
     * Update the specified user.
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        // Remove password if it's empty (for partial updates)
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        $user = $this->userService->updateUser($id, $data);

        return $this->success(
            new UserResource($user),
            'User updated successfully'
        );
    }

    /**
     * Remove the specified user.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->userService->deleteUser($id);

        return $this->success(null, 'User deleted successfully');
    }

    /**
     * Get active users.
     */
    public function active(): JsonResponse
    {
        $users = $this->userService->getActiveUsers();

        return $this->success(
            UserResource::collection($users),
            'Active users retrieved successfully'
        );
    }

    /**
     * Bulk delete users.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:users,id',
        ]);

        $count = $this->userService->bulkDeleteUsers($request->input('ids'));

        return $this->success(
            ['deleted_count' => $count],
            "Successfully deleted {$count} users"
        );
    }

    /**
     * Get user statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->userService->getUserStatistics();

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Check if email exists.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $exists = $this->userService->checkEmailExists($request->input('email'));

        return $this->success(
            ['exists' => $exists],
            $exists ? 'Email already exists' : 'Email is available'
        );
    }
}
