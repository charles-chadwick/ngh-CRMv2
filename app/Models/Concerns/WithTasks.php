<?php

namespace App\Models\Concerns;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds a polymorphic `tasks` relationship backed by the {@see Task} model.
 */
trait WithTasks
{
    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'subject')->orderBy('due_at');
    }

    /**
     * Paginate the model's tasks for display, preserving the current query string.
     *
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginatedTasks(int $perPage = 5): LengthAwarePaginator
    {
        return $this->tasks()
            ->with('assignee', 'creationActivity.causer', 'updateActivity.causer')
            ->paginate($perPage, ['*'], 'tasks_page')
            ->withQueryString();
    }
}
