<?php

namespace App\Services;

use App\Exceptions\BaseException;
use App\Exceptions\User\MailExistException;
use App\Exceptions\User\NotFoundException;
use App\Http\Repos\UserRepository;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    protected UserRepository $repository;

    /**
     * UserService constructor.
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all users with pagination
     */
    public function getAllUsers(int $perPage = 15) {}

    /**
     * Get user by ID
     *
     * @throws BaseException
     */
    public function getUserById(int $id): User
    {
        $user = $this->repository->find($id, ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at']);

        if (! $user) {
            throw new NotFoundException($id);
        }

        return $user;
    }

    /**
     * Create a new user
     *
     * @throws BaseException
     */
    public function createUser(array $data): User
    {
        // Check if email already exists
        if ($this->repository->emailExists($data['email'])) {
            throw new MailExistException($data['email']);
        }

        DB::beginTransaction();
        try {
            $user = $this->repository->createUser($data);

            // Additional operations can be added here
            // e.g., send welcome email, create user profile, etc.

            DB::commit();
            Log::info('User created successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return $user;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating user: '.$e->getMessage(), ['data' => $data]);
            throw new BaseException('Failed to create user', 500, 'CREATE_USER_FAILED');
        }
    }

    /**
     * Update user
     *
     * @throws BaseException
     */
    public function updateUser(int $id, array $data): User
    {
        $user = $this->getUserById($id);

        // Check if email is being changed and if it already exists
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if ($this->repository->emailExists($data['email'], $id)) {
                throw new BaseException('Email already exists', 422, 'EMAIL_EXISTS', ['email' => $data['email']]);
            }
        }

        DB::beginTransaction();
        try {
            $updated = $this->repository->updateUser($id, $data);

            if (! $updated) {
                throw new BaseException('Failed to update user', 500, 'UPDATE_USER_FAILED');
            }

            DB::commit();
            Log::info('User updated successfully', ['user_id' => $id]);

            return $this->getUserById($id);
        } catch (BaseException $e) {
            DB::rollback();
            throw $e;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating user: '.$e->getMessage(), ['user_id' => $id, 'data' => $data]);
            throw new BaseException('Failed to update user', 500, 'UPDATE_USER_FAILED');
        }
    }

    /**
     * Delete user
     *
     * @throws BaseException
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->getUserById($id);

        DB::beginTransaction();
        try {
            // Additional cleanup operations can be added here
            // e.g., delete user-related data, send notification, etc.

            $deleted = $this->repository->delete($id);

            if (! $deleted) {
                throw new BaseException('Failed to delete user', 500, 'DELETE_USER_FAILED');
            }

            DB::commit();
            Log::info('User deleted successfully', ['user_id' => $id]);

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error deleting user: '.$e->getMessage(), ['user_id' => $id]);
            throw new BaseException('Failed to delete user', 500, 'DELETE_USER_FAILED');
        }
    }

    /**
     * Search users
     */
    public function searchUsers(string $keyword, int $perPage = 15): LengthAwarePaginator
    {
        try {
            return $this->repository->searchUsers($keyword, $perPage);
        } catch (\Exception $e) {
            Log::error('Error searching users: '.$e->getMessage());
            throw new BaseException('Failed to search users', 500, 'SEARCH_USERS_FAILED');
        }
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): Collection
    {
        try {
            return $this->repository->getActiveUsers(['id', 'name', 'email', 'email_verified_at']);
        } catch (\Exception $e) {
            Log::error('Error fetching active users: '.$e->getMessage());
            throw new BaseException('Failed to fetch active users', 500, 'FETCH_ACTIVE_USERS_FAILED');
        }
    }

    /**
     * Bulk delete users
     *
     * @throws BaseException
     */
    public function bulkDeleteUsers(array $ids): int
    {
        DB::beginTransaction();
        try {
            $count = $this->repository->deleteMultiple($ids);

            DB::commit();
            Log::info('Users bulk deleted successfully', ['count' => $count]);

            return $count;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error bulk deleting users: '.$e->getMessage());
            throw new BaseException('Failed to bulk delete users', 500, 'BULK_DELETE_USERS_FAILED');
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        try {
            return [
                'total_users' => $this->repository->count(),
                'verified_users' => $this->repository->countVerifiedUsers(),
                'unverified_users' => $this->repository->countUnverifiedUsers(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching user statistics: '.$e->getMessage());
            throw new BaseException('Failed to fetch statistics', 500, 'FETCH_STATISTICS_FAILED');
        }
    }

    /**
     * Check if user exists by email
     */
    public function checkEmailExists(string $email): bool
    {
        return $this->repository->emailExists($email);
    }
}
