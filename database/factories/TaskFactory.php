<?php

namespace Database\Factories;

use App\Enum\TaskStatus;
use App\Enum\TaskType;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(TaskType::cases())->value,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => TaskStatus::Open->value,
            'due_at' => fake()->dateTimeBetween('now', '+2 weeks'),
            'all_day' => false,
            'remind_at' => null,
            'reminded_at' => null,
            'completed_at' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'due_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }

    public function dueToday(): static
    {
        return $this->state(fn (): array => [
            'due_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Completed->value,
            'completed_at' => now(),
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn (): array => ['all_day' => true]);
    }

    public function forSubject(Model $subject): static
    {
        return $this->state(fn (): array => [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    public function assignedTo(Model $assignee): static
    {
        return $this->state(fn (): array => [
            'assignee_type' => $assignee->getMorphClass(),
            'assignee_id' => $assignee->getKey(),
        ]);
    }
}
