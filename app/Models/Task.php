<?php

namespace App\Models;

use App\Enum\TaskStatus;
use App\Enum\TaskType;
use App\Models\Concerns\WithActivity;
use Carbon\CarbonInterface;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type',
    'title',
    'description',
    'status',
    'due_at',
    'all_day',
    'remind_at',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes, WithActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'status' => TaskStatus::class,
            'due_at' => 'datetime',
            'remind_at' => 'datetime',
            'reminded_at' => 'datetime',
            'completed_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * The record this task is about (Company, Customer, or Lead).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The person responsible for this task (User or Customer).
     */
    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', TaskStatus::Open);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', TaskStatus::Completed);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', TaskStatus::Open)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function scopeDueOn(Builder $query, CarbonInterface $date): void
    {
        $query->whereDate('due_at', $date);
    }

    public function scopeForAssignee(Builder $query, Model $assignee): void
    {
        $query->where('assignee_type', $assignee->getMorphClass())
            ->where('assignee_id', $assignee->getKey());
    }

    public function scopeBetween(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        $query->whereNotNull('due_at')->whereBetween('due_at', [$start, $end]);
    }

    public function markCompleted(): void
    {
        $this->status = TaskStatus::Completed;
        $this->completed_at = now();
        $this->save();
    }

    public function markCanceled(): void
    {
        $this->status = TaskStatus::Canceled;
        $this->save();
    }

    public function reopen(): void
    {
        $this->status = TaskStatus::Open;
        $this->completed_at = null;
        $this->save();
    }
}
