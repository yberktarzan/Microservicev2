<?php

namespace App\Http\Controllers;

use App\Exceptions\BaseException;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');

            if ($search) {
                $users = $this->userService->searchUsers($search, $perPage);
            } else {
                $users = $this->userService->getAllUsers($perPage);
            }

            return $this->success($users, 'Users retrieved successfully');
        } catch (BaseException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrorData());
        } catch (\Exception $e) {
            Log::error('Error in UserController@index: '.$e->getMessage());

            return $this->error('Failed to retrieve users', 500);
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(UserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return $this->success(
                new UserResource($user),
                'User created successfully',
                201
            );
        } catch (BaseException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrorData());
        } catch (\Exception $e) {
            Log::error('Error in UserController@store: '.$e->getMessage());

            return $this->error('Failed to create user', 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            return $this->success(
                new UserResource($user),
                'User retrieved successfully'
            );
        } catch (BaseException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrorData());
        } catch (\Exception $e) {
            Log::error('Error in UserController@show: '.$e->getMessage());

            return $this->error('Failed to retrieve user', 500);
        }
    }

    /**
     * Update the specified user.
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        try {
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
        } catch (BaseException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrorData());
        } catch (\Exception $e) {
            Log::error('Error in UserController@update: '.$e->getMessage());

            return $this->error('Failed to update user', 500);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return $this->success(null, 'User deleted successfully');
        } catch (BaseException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrorData());
        } catch (\Exception $e) {
            Log::error('Error in UserController@destroy: '.$e->getMessage());

            return $this->error('Failed to delete user', 500);
        }
    }

    /**
     * Get active users.
     */
    public function active(): JsonResponse
    {
        try {
            $users = $this->userService->getActiveUsers();

            return $this->success(
                UserResource::collection($users),
                'Active users retrieved successfully'
            );
        } catch (BaseException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrorData());
        } catch (\Exception $e) {
            Log::error('Error in UserController@active: '.$e->getMessage());

            return $this->error('Failed to retrieve active users', 500);
        }
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

        try {
            $count = $this->userService->bulkDeleteUsers($request->input('ids'));

            return $this->success(
                ['deleted_count' => $count],
                "Successfully deleted {$count} users"
            );
        } catch (BaseException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrorData());
        } catch (\Exception $e) {
            Log::error('Error in UserController@bulkDelete: '.$e->getMessage());

            return $this->error('Failed to bulk delete users', 500);
        }
    }

    /**
     * Get user statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->userService->getUserStatistics();

            return $this->success($stats, 'Statistics retrieved successfully');
        } catch (BaseException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrorData());
        } catch (\Exception $e) {
            Log::error('Error in UserController@statistics: '.$e->getMessage());

            return $this->error('Failed to retrieve statistics', 500);
        }
    }

    /**
     * Check if email exists.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $exists = $this->userService->checkEmailExists($request->input('email'));

            return $this->success(
                ['exists' => $exists],
                $exists ? 'Email already exists' : 'Email is available'
            );
        } catch (\Exception $e) {
            Log::error('Error in UserController@checkEmail: '.$e->getMessage());

            return $this->error('Failed to check email', 500);
        }
    }
}
