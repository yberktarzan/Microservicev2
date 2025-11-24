<?php

namespace App\Services;

use App\Exceptions\User\CreateFailedException;
use App\Exceptions\User\DeleteFailedException;
use App\Exceptions\User\MailExistException;
use App\Exceptions\User\NotFoundException;
use App\Exceptions\User\UpdateFailedException;
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
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()->paginate($perPage, ['id', 'name', 'email', 'email_verified_at', 'created_at']);
    }

    /**
     * Get user by ID
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
     */
    public function createUser(array $data): User
    {
        // Check if email already exists
        if ($this->repository->emailExists($data['email'])) {
            throw new MailExistException($data['email']);
        }

        DB::beginTransaction();

        $user = $this->repository->createUser($data);

        if (! $user) {
            DB::rollback();
            Log::error('Failed to create user', ['data' => $data]);
            throw new CreateFailedException;
        }

        DB::commit();
        Log::info('User created successfully', ['user_id' => $user->id, 'email' => $user->email]);

        return $user;
    }

    /**
     * Update user
     */
    public function updateUser(int $id, array $data): User
    {
        $user = $this->getUserById($id);

        // Check if email is being changed and if it already exists
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if ($this->repository->emailExists($data['email'], $id)) {
                throw new MailExistException($data['email']);
            }
        }

        DB::beginTransaction();

        $updated = $this->repository->updateUser($id, $data);

        if (! $updated) {
            DB::rollback();
            Log::error('Failed to update user', ['user_id' => $id, 'data' => $data]);
            throw new UpdateFailedException($id);
        }

        DB::commit();
        Log::info('User updated successfully', ['user_id' => $id]);

        return $this->getUserById($id);
    }

    /**
     * Delete user
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->getUserById($id);

        DB::beginTransaction();

        $deleted = $this->repository->delete($id);

        if (! $deleted) {
            DB::rollback();
            Log::error('Failed to delete user', ['user_id' => $id]);
            throw new DeleteFailedException($id);
        }

        DB::commit();
        Log::info('User deleted successfully', ['user_id' => $id]);

        return true;
    }

    /**
     * Search users
     */
    public function searchUsers(string $keyword, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->searchUsers($keyword, $perPage);
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): Collection
    {
        return $this->repository->getActiveUsers(['id', 'name', 'email', 'email_verified_at']);
    }

    /**
     * Bulk delete users
     */
    public function bulkDeleteUsers(array $ids): int
    {
        DB::beginTransaction();

        $count = $this->repository->deleteMultiple($ids);

        if ($count === 0) {
            DB::rollback();
            Log::error('Failed to delete any users', ['ids' => $ids]);
            throw new DeleteFailedException(0);
        }

        DB::commit();
        Log::info('Users bulk deleted successfully', ['count' => $count]);

        return $count;
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        return [
            'total_users' => $this->repository->count(),
            'verified_users' => $this->repository->countVerifiedUsers(),
            'unverified_users' => $this->repository->countUnverifiedUsers(),
        ];
    }

    /**
     * Check if user exists by email
     */
    public function checkEmailExists(string $email): bool
    {
        return $this->repository->emailExists($email);
    }
}
