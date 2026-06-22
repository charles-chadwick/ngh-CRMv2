<?php

namespace App\Http\Controllers;

use App\Enum\TaskStatus;
use App\Http\Requests\TaskRequest;
use App\Http\Requests\TaskStatusRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\NoteContentSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    /**
     * Models a task may be about, keyed by the client `subject_type` value.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $subjects = [
        'customer' => Customer::class,
        'company' => Company::class,
        'lead' => Lead::class,
    ];

    /**
     * Models a task may be assigned to, keyed by the client `assignee_type` value.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $assignees = [
        'user' => User::class,
        'customer' => Customer::class,
    ];

    public function store(TaskRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $task = new Task($this->taskAttributes($request));

        $subject = $this->resolveSubject($validated['subject_type'] ?? null, $validated['subject_id'] ?? null);
        if ($subject !== null) {
            $task->subject()->associate($subject);
        }

        $assignee = $this->resolveAssignee($validated['assignee_type'] ?? null, $validated['assignee_id'] ?? null);
        if ($assignee !== null) {
            $task->assignee()->associate($assignee);
        }

        $task->save();

        return back();
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        $attributes = $request->safe()->only([
            'type', 'title', 'description', 'due_at', 'all_day', 'remind_at',
        ]);
        $attributes['all_day'] = (bool) ($attributes['all_day'] ?? false);

        $task->fill($attributes);

        if (array_key_exists('subject_type', $validated)) {
            $subject = $this->resolveSubject($validated['subject_type'], $validated['subject_id'] ?? null);
            $task->subject()->associate($subject);
        }

        if (array_key_exists('assignee_type', $validated)) {
            $assignee = $this->resolveAssignee($validated['assignee_type'], $validated['assignee_id'] ?? null);
            $task->assignee()->associate($assignee);
        }

        $task->save();

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back();
    }

    public function updateStatus(TaskStatusRequest $request, Task $task): RedirectResponse
    {
        $status = TaskStatus::from($request->validated('status'));

        match ($status) {
            TaskStatus::Completed => $task->markCompleted(),
            TaskStatus::Canceled => $task->markCanceled(),
            TaskStatus::Open => $task->reopen(),
        };

        $log_note = $request->validated('log_note');
        if ($status === TaskStatus::Completed && filled($log_note) && $task->subject !== null) {
            $task->subject->notes()->create([
                'type' => 'Follow Up',
                'title' => 'Task completed: '.$task->title,
                'content' => app(NoteContentSanitizer::class)->clean($log_note),
            ]);
        }

        return back();
    }

    /**
     * Pull the writable task attributes, defaulting the reminder to the due date.
     *
     * @return array<string, mixed>
     */
    protected function taskAttributes(TaskRequest $request): array
    {
        $attributes = $request->safe()->only([
            'type', 'title', 'description', 'due_at', 'all_day', 'remind_at',
        ]);

        $attributes['all_day'] = (bool) ($attributes['all_day'] ?? false);

        if (empty($attributes['remind_at']) && ! empty($attributes['due_at'])) {
            $due = Carbon::parse($attributes['due_at']);
            $attributes['remind_at'] = $attributes['all_day'] ? $due->copy()->setTime(9, 0) : $due;
        }

        return $attributes;
    }

    protected function resolveSubject(?string $type, ?int $id): ?Model
    {
        if ($type === null || $id === null) {
            return null;
        }

        return $this->subjects[$type]::findOrFail($id);
    }

    protected function resolveAssignee(?string $type, ?int $id): ?Model
    {
        if ($type === null || $id === null) {
            return null;
        }

        return $this->assignees[$type]::findOrFail($id);
    }
}
