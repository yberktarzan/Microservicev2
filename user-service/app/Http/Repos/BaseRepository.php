<?php

namespace App\Http\Repos;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->model = $this->getModel();
    }

    /**
     * Get the model instance
     */
    abstract protected function getModel(): Model;

    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->get();
    }

    /**
     * Find a record by ID
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->select($columns)->find($id);
    }

    /**
     * Find a record by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->select($columns)->findOrFail($id);
    }

    /**
     * Find records by field
     *
     * @param  mixed  $value
     */
    public function findBy(string $field, $value, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->where($field, $value)->get();
    }

    /**
     * Find first record by field
     *
     * @param  mixed  $value
     */
    public function findByFirst(string $field, $value, array $columns = ['*']): ?Model
    {
        return $this->model->select($columns)->where($field, $value)->first();
    }

    /**
     * Find records by multiple criteria
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->select($columns);

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get();
    }

    /**
     * Find first record by multiple criteria
     */
    public function findWhereFirst(array $criteria, array $columns = ['*']): ?Model
    {
        $query = $this->model->select($columns);

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->first();
    }

    /**
     * Find records where field is in array
     */
    public function findWhereIn(string $field, array $values, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->whereIn($field, $values)->get();
    }

    /**
     * Find records where field is not in array
     */
    public function findWhereNotIn(string $field, array $values, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->whereNotIn($field, $values)->get();
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->find($id);

        if ($record) {
            return $record->update($data);
        }

        return false;
    }

    /**
     * Update or create a record
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Delete a record
     */
    public function delete(int $id): bool
    {
        $record = $this->find($id);

        if ($record) {
            return $record->delete();
        }

        return false;
    }

    /**
     * Delete multiple records
     */
    public function deleteMultiple(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Delete records by criteria
     */
    public function deleteWhere(array $criteria): int
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->delete();
    }

    /**
     * Force delete a record (for soft deletes)
     */
    public function forceDelete(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);

        if ($record) {
            return $record->forceDelete();
        }

        return false;
    }

    /**
     * Restore a soft deleted record
     */
    public function restore(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);

        if ($record) {
            return $record->restore();
        }

        return false;
    }

    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->count();
    }

    /**
     * Check if record exists
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * Check if records exist by criteria
     */
    public function existsWhere(array $criteria): bool
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }

    /**
     * Get first record
     */
    public function first(array $columns = ['*']): ?Model
    {
        return $this->model->select($columns)->first();
    }

    /**
     * Get last record
     */
    public function last(array $columns = ['*']): ?Model
    {
        return $this->model->select($columns)->orderBy('id', 'desc')->first();
    }

    /**
     * Order by field
     *
     * @return $this
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->model = $this->model->orderBy($field, $direction);

        return $this;
    }

    /**
     * Load relationships
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function with($relations): self
    {
        $this->model = $this->model->with($relations);

        return $this;
    }

    /**
     * Get records with trashed (soft deleted)
     *
     * @return $this
     */
    public function withTrashed(): self
    {
        $this->model = $this->model->withTrashed();

        return $this;
    }

    /**
     * Get only trashed records
     *
     * @return $this
     */
    public function onlyTrashed(): self
    {
        $this->model = $this->model->onlyTrashed();

        return $this;
    }

    /**
     * Begin database transaction
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * Commit database transaction
     */
    public function commit(): void
    {
        DB::commit();
    }

    /**
     * Rollback database transaction
     */
    public function rollback(): void
    {
        DB::rollback();
    }

    /**
     * Get the underlying model query builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->model->query();
    }

    /**
     * Get fresh model instance
     */
    public function getModelInstance(): Model
    {
        return $this->getModel();
    }

    /**
     * Chunk records
     */
    public function chunk(int $count, callable $callback): bool
    {
        return $this->model->chunk($count, $callback);
    }

    /**
     * Get records with limit
     */
    public function limit(int $limit, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->limit($limit)->get();
    }

    /**
     * Get records with offset
     */
    public function offset(int $offset, array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->offset($offset)->get();
    }

    /**
     * Get sum of a column
     *
     * @return mixed
     */
    public function sum(string $column, array $criteria = [])
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->sum($column);
    }

    /**
     * Get average of a column
     *
     * @return mixed
     */
    public function avg(string $column, array $criteria = [])
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->avg($column);
    }

    /**
     * Get minimum value of a column
     *
     * @return mixed
     */
    public function min(string $column, array $criteria = [])
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->min($column);
    }

    /**
     * Get maximum value of a column
     *
     * @return mixed
     */
    public function max(string $column, array $criteria = [])
    {
        $query = $this->model->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->max($column);
    }

    /**
     * Insert multiple records
     */
    public function insert(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * Increment a column value
     */
    public function increment(int $id, string $column, int $amount = 1): int
    {
        return $this->model->where('id', $id)->increment($column, $amount);
    }

    /**
     * Decrement a column value
     */
    public function decrement(int $id, string $column, int $amount = 1): int
    {
        return $this->model->where('id', $id)->decrement($column, $amount);
    }

    /**
     * Search records by keyword in specified fields
     */
    public function search(string $keyword, array $fields, array $columns = ['*']): Collection
    {
        $query = $this->model->select($columns);

        $query->where(function ($q) use ($keyword, $fields) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$keyword}%");
            }
        });

        return $query->get();
    }

    /**
     * Pluck specific columns
     *
     * @return \Illuminate\Support\Collection
     */
    public function pluck(string $column, ?string $key = null)
    {
        return $this->model->pluck($column, $key);
    }

    /**
     * Sync relationships (many-to-many)
     */
    public function sync(int $id, string $relation, array $data): array
    {
        $record = $this->find($id);

        if ($record) {
            return $record->$relation()->sync($data);
        }

        return [];
    }

    /**
     * Attach relationships (many-to-many)
     */
    public function attach(int $id, string $relation, array $data): void
    {
        $record = $this->find($id);

        if ($record) {
            $record->$relation()->attach($data);
        }
    }

    /**
     * Detach relationships (many-to-many)
     */
    public function detach(int $id, string $relation, ?array $data = null): int
    {
        $record = $this->find($id);

        if ($record) {
            return $record->$relation()->detach($data);
        }

        return 0;
    }
}
