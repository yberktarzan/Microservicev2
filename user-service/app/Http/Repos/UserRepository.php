<?php

namespace App\Http\Repos;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository
{
    /**
     * Get the model instance
     */
    protected function getModel(): Model
    {
        return new User;
    }

    /**
     * Create user with hashed password
     */
    public function createUser(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->create($data);
    }

    /**
     * Update user with optional password hashing
     */
    public function updateUser(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->update($id, $data);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findByFirst('email', $email);
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active users
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveUsers(array $columns = ['*'])
    {
        return $this->model
            ->select($columns)
            ->whereNotNull('email_verified_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Search users by name or email
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function searchUsers(string $keyword, int $perPage = 15)
    {
        return $this->model
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get user with relationships
     */
    public function findWithRelations(int $id, array $relations = []): ?User
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Bulk update users
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        return $this->model->whereIn('id', $ids)->update($data);
    }

    /**
     * Count verified users
     */
    public function countVerifiedUsers(): int
    {
        return $this->model->whereNotNull('email_verified_at')->count();
    }

    /**
     * Count unverified users
     */
    public function countUnverifiedUsers(): int
    {
        return $this->model->whereNull('email_verified_at')->count();
    }
}
