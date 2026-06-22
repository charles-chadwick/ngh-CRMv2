# Tasks & Activities Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a unified `Task` system (to-dos and scheduled events) that attaches polymorphically to Company/Customer/Lead, is assigned to a User or Customer, supports completion + in-app reminders, and is surfaced as record-page lists, a "My activities" page with a List/Calendar toggle (FullCalendar), and a dashboard widget.

**Architecture:** A single `tasks` table with a nullable polymorphic `subject` (the record it's about) and a nullable polymorphic `assignee` (User or Customer). `all_day` unifies dateless to-dos with timed events. Backend mirrors the existing `Note`/`Contact` polymorphic controllers and the `ActAsController@options` search pattern; reminders use a database `Notification` plus a scheduled console command. Frontend mirrors the `Components/Notes/` section pattern and uses FullCalendar for the calendar surface.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3 (shadcn-vue UI components), Pest 4, FullCalendar (`@fullcalendar/vue3` + core/daygrid/timegrid/interaction).

## Global Constraints

- PHP 8.4; Laravel 13; Inertia v3; Pest 4. (verbatim from spec/CLAUDE.md)
- Naming: variables `snake_case`, methods/functions `camelCase`, classes `TitleCase`. Enum keys `TitleCase`.
- PHP: curly braces on all control structures; constructor property promotion; explicit return types and param type hints; PHPDoc array shapes.
- Enums live in `app/Enum` (singular) and expose a static `options(): array` returning `[{value,label}]`.
- Polymorphic morphs store the fully-qualified class name (project default — see `notes` migration / `NoteController`). Client sends a short key (`customer`/`company`/`lead`, `user`/`customer`) which the controller maps to a class via a whitelist.
- Times stored UTC; rendered in app timezone. All-day items are pinned to a date.
- Model name is `Task` (NOT `Activity`) — "activity" is reserved for the Spatie activity log (`WithActivity`, `creationActivity`, `updateActivity`).
- Seeders/factories reference only existing users/customers/companies/leads — never create them as fallbacks. Pull with `Model::inRandomOrder()->first()?->id`, leaving `null` when none exist.
- Run `vendor/bin/pint --dirty --format agent` after PHP changes.
- Run tests with `php artisan test --compact --filter=<name>`.

---

### Task 1: Enums, migration, `Task` model, and factory

**Files:**
- Create: `app/Enum/TaskType.php`
- Create: `app/Enum/TaskStatus.php`
- Create: `database/migrations/2026_06_22_000001_create_tasks_table.php` (via artisan)
- Create: `app/Models/Task.php`
- Create: `database/factories/TaskFactory.php`
- Test: `tests/Feature/TaskModelTest.php`

**Interfaces:**
- Produces:
  - `App\Enum\TaskType` cases `Call,Meeting,Email,FollowUp,ToDo` (values `'Call','Meeting','Email','Follow Up','To-Do'`); static `options(): array`.
  - `App\Enum\TaskStatus` cases `Open,Completed,Canceled` (values match names); static `options(): array`.
  - `App\Models\Task` with `subject(): MorphTo`, `assignee(): MorphTo`; scopes `open`, `completed`, `overdue`, `dueOn(CarbonInterface)`, `forAssignee(Model)`, `between(CarbonInterface,CarbonInterface)`; methods `markCompleted(): void`, `markCanceled(): void`, `reopen(): void`. Casts: `type`,`status` enums; `due_at`,`remind_at`,`reminded_at`,`completed_at` datetime; `all_day` boolean.
  - `Database\Factories\TaskFactory` with states `overdue()`, `dueToday()`, `completed()`, `allDay()`, `forSubject(Model)`, `assignedTo(Model)`.

- [ ] **Step 1: Create the enums**

`app/Enum/TaskType.php`:
```php
<?php

namespace App\Enum;

enum TaskType: string
{
    case Call = 'Call';
    case Meeting = 'Meeting';
    case Email = 'Email';
    case FollowUp = 'Follow Up';
    case ToDo = 'To-Do';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->value,
        ], self::cases());
    }
}
```

`app/Enum/TaskStatus.php`:
```php
<?php

namespace App\Enum;

enum TaskStatus: string
{
    case Open = 'Open';
    case Completed = 'Completed';
    case Canceled = 'Canceled';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->value,
        ], self::cases());
    }
}
```

- [ ] **Step 2: Create the migration**

Run: `php artisan make:migration create_tasks_table --no-interaction`

Replace the generated file body with:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('subject');
            $table->nullableMorphs('assignee');
            $table->string('type');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status')->default('Open');
            $table->dateTime('due_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->dateTime('remind_at')->nullable();
            $table->dateTime('reminded_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
```

- [ ] **Step 3: Create the model**

`app/Models/Task.php`:
```php
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
        $this->update(['status' => TaskStatus::Completed, 'completed_at' => now()]);
    }

    public function markCanceled(): void
    {
        $this->update(['status' => TaskStatus::Canceled]);
    }

    public function reopen(): void
    {
        $this->update(['status' => TaskStatus::Open, 'completed_at' => null]);
    }
}
```

- [ ] **Step 4: Create the factory**

`database/factories/TaskFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Enum\TaskStatus;
use App\Enum\TaskType;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'status' => TaskStatus::Open->value,
            'due_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }

    public function dueToday(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Open->value,
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
```

- [ ] **Step 5: Write the failing model test**

`tests/Feature/TaskModelTest.php`:
```php
<?php

use App\Enum\TaskStatus;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('casts attributes and resolves polymorphic relations', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->create();

    $task = Task::factory()
        ->forSubject($customer)
        ->assignedTo($user)
        ->allDay()
        ->create();

    expect($task->all_day)->toBeTrue()
        ->and($task->status)->toBe(TaskStatus::Open)
        ->and($task->subject->is($customer))->toBeTrue()
        ->and($task->assignee->is($user))->toBeTrue();
});

it('scopes overdue to open, past-due tasks only', function () {
    Task::factory()->overdue()->create();
    Task::factory()->completed()->overdue()->create();
    Task::factory()->create(['due_at' => now()->addWeek()]);

    expect(Task::overdue()->count())->toBe(1);
});

it('marks a task completed and reopens it', function () {
    $task = Task::factory()->create();

    $task->markCompleted();
    expect($task->fresh()->status)->toBe(TaskStatus::Completed)
        ->and($task->fresh()->completed_at)->not->toBeNull();

    $task->reopen();
    expect($task->fresh()->status)->toBe(TaskStatus::Open)
        ->and($task->fresh()->completed_at)->toBeNull();
});

it('filters by assignee', function () {
    $user = User::factory()->create();
    Task::factory()->assignedTo($user)->create();
    Task::factory()->assignedTo(User::factory()->create())->create();

    assertDatabaseHas('tasks', ['assignee_id' => $user->id]);
    expect(Task::forAssignee($user)->count())->toBe(1);
});
```

- [ ] **Step 6: Run and verify the test fails (table not yet migrated in test db)**

Run: `php artisan test --compact --filter=TaskModelTest`
Expected: FAIL (no `tasks` table / class wiring) before migration runs in the test bootstrap.

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --compact --filter=TaskModelTest`
Expected: PASS (RefreshDatabase runs the new migration).

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enum/TaskType.php app/Enum/TaskStatus.php database/migrations/*_create_tasks_table.php app/Models/Task.php database/factories/TaskFactory.php tests/Feature/TaskModelTest.php
git commit -m "feat: tasks foundation (enums, migration, model, factory)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: `WithTasks` concern on Company, Customer, Lead

**Files:**
- Create: `app/Models/Concerns/WithTasks.php`
- Modify: `app/Models/Company.php`, `app/Models/Customer.php`, `app/Models/Lead.php` (add `use WithTasks;` to the trait list and `WithTasks` import)
- Test: `tests/Feature/WithTasksTest.php`

**Interfaces:**
- Consumes: `App\Models\Task` (Task 1).
- Produces: trait `App\Models\Concerns\WithTasks` with `tasks(): MorphMany` (ordered by `due_at`) and `paginatedTasks(int $perPage = 5): LengthAwarePaginator` (page name `tasks_page`, eager-loads `assignee`, `creationActivity.causer`, `updateActivity.causer`).

- [ ] **Step 1: Write the failing test**

`tests/Feature/WithTasksTest.php`:
```php
<?php

use App\Models\Company;
use App\Models\Lead;
use App\Models\Task;

it('exposes a polymorphic tasks relation ordered by due date', function () {
    $company = Company::factory()->create();
    $later = Task::factory()->forSubject($company)->create(['due_at' => now()->addWeek()]);
    $sooner = Task::factory()->forSubject($company)->create(['due_at' => now()->addDay()]);

    expect($company->tasks()->pluck('id')->all())->toBe([$sooner->id, $later->id]);
});

it('paginates a record\'s tasks under the tasks_page name', function () {
    $lead = Lead::factory()->create();
    Task::factory()->count(7)->forSubject($lead)->create();

    $paginated = $lead->paginatedTasks();

    expect($paginated->perPage())->toBe(5)
        ->and($paginated->total())->toBe(7)
        ->and($paginated->getPageName())->toBe('tasks_page');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=WithTasksTest`
Expected: FAIL with "Call to undefined method ...::tasks()".

- [ ] **Step 3: Create the concern**

`app/Models/Concerns/WithTasks.php`:
```php
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
```

- [ ] **Step 4: Wire the trait into the three models**

In `app/Models/Company.php`, `app/Models/Customer.php`, `app/Models/Lead.php`: add `use App\Models\Concerns\WithTasks;` with the other concern imports, and add `WithTasks` to the `use HasFactory, SoftDeletes, WithActivity, WithNotes, ...;` line in each class.

Example for `Lead.php` (the `use` statement inside the class):
```php
    use HasFactory, SoftDeletes, WithActivity, WithNotes, WithSearch, WithSort, WithTasks;
```

- [ ] **Step 5: Run to verify it passes**

Run: `php artisan test --compact --filter=WithTasksTest`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Concerns/WithTasks.php app/Models/Company.php app/Models/Customer.php app/Models/Lead.php tests/Feature/WithTasksTest.php
git commit -m "feat: WithTasks concern on company, customer, lead

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: `TaskRequest`, `TaskController@store`, route, subject/assignee resolution

**Files:**
- Create: `app/Http/Requests/TaskRequest.php`
- Create: `app/Http/Controllers/TaskController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TaskControllerStoreTest.php`

**Interfaces:**
- Consumes: `Task` (Task 1), `WithTasks` (Task 2).
- Produces:
  - `TaskRequest` validating `subject_type` in `[customer,company,lead]` + `subject_id` (both `requiredIf(!update)`, nullable allowed for standalone), `assignee_type` in `[user,customer]` nullable + `assignee_id` nullable, `type` in `TaskType` values, `title` required string max 255, `description` nullable string, `due_at` nullable date, `all_day` boolean, `remind_at` nullable date.
  - `TaskController` with protected `$subjects` (`customer`=>Customer, `company`=>Company, `lead`=>Lead) and `$assignees` (`user`=>User, `customer`=>Customer) whitelists; `store(TaskRequest): RedirectResponse`; protected helpers `taskAttributes(TaskRequest): array`, `resolveSubject(?string,?int): ?Model`, `resolveAssignee(?string,?int): ?Model`.
  - Route `POST /tasks` named `tasks.store`.
  - Behaviour: on create, if `remind_at` is null and `due_at` is set, default `remind_at` to `due_at` (when `all_day`, to 9:00am of that date).

- [ ] **Step 1: Write the failing test**

`tests/Feature/TaskControllerStoreTest.php`:
```php
<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

beforeEach(fn () => actingAs(User::factory()->create()));

it('creates a task against a company assigned to a user', function () {
    $company = Company::factory()->create();
    $assignee = User::factory()->create();

    post(route('tasks.store'), [
        'subject_type' => 'company',
        'subject_id' => $company->id,
        'assignee_type' => 'user',
        'assignee_id' => $assignee->id,
        'type' => 'Call',
        'title' => 'Quarterly review call',
        'due_at' => now()->addDay()->toDateTimeString(),
        'all_day' => false,
    ])->assertRedirect();

    $task = Task::first();
    expect($task->subject->is($company))->toBeTrue()
        ->and($task->assignee->is($assignee))->toBeTrue()
        ->and($task->title)->toBe('Quarterly review call');
});

it('creates a standalone task with no subject', function () {
    post(route('tasks.store'), [
        'type' => 'To-Do',
        'title' => 'Personal reminder',
        'due_at' => now()->addDay()->toDateTimeString(),
    ])->assertRedirect();

    assertDatabaseHas('tasks', ['title' => 'Personal reminder', 'subject_id' => null]);
});

it('defaults remind_at to due_at when omitted', function () {
    $due = now()->addDays(3)->startOfMinute();

    post(route('tasks.store'), [
        'type' => 'To-Do',
        'title' => 'Reminder defaulting',
        'due_at' => $due->toDateTimeString(),
    ])->assertRedirect();

    expect(Task::first()->remind_at->toDateTimeString())->toBe($due->toDateTimeString());
});

it('assigns a customer as assignee', function () {
    $customer = Customer::factory()->create();

    post(route('tasks.store'), [
        'type' => 'Follow Up',
        'title' => 'Waiting on docs',
        'assignee_type' => 'customer',
        'assignee_id' => $customer->id,
    ])->assertRedirect();

    expect(Task::first()->assignee->is($customer))->toBeTrue();
});

it('rejects an invalid type', function () {
    post(route('tasks.store'), ['type' => 'Bogus', 'title' => 'x'])
        ->assertSessionHasErrors('type');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=TaskControllerStoreTest`
Expected: FAIL (route `tasks.store` not defined).

- [ ] **Step 3: Create the request**

`app/Http/Requests/TaskRequest.php`:
```php
<?php

namespace App\Http\Requests;

use App\Enum\TaskType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_type' => ['nullable', 'string', Rule::in(['customer', 'company', 'lead'])],
            'subject_id' => ['nullable', 'integer', 'required_with:subject_type'],
            'assignee_type' => ['nullable', 'string', Rule::in(['user', 'customer'])],
            'assignee_id' => ['nullable', 'integer', 'required_with:assignee_type'],
            'type' => ['required', 'string', Rule::in(array_column(TaskType::cases(), 'value'))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'all_day' => ['boolean'],
            'remind_at' => ['nullable', 'date'],
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

`app/Http/Controllers/TaskController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
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
```

- [ ] **Step 5: Add the route**

In `routes/web.php`, alongside the notes/contacts routes, add:
```php
use App\Http\Controllers\TaskController;

Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
```
(Place the `use` with the other controller imports; place the route inside the same authenticated web group the notes routes live in.)

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test --compact --filter=TaskControllerStoreTest`
Expected: PASS.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/TaskRequest.php app/Http/Controllers/TaskController.php routes/web.php tests/Feature/TaskControllerStoreTest.php
git commit -m "feat: create tasks via TaskController@store

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: `TaskController@update` and `@destroy`

**Files:**
- Modify: `app/Http/Controllers/TaskController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TaskControllerUpdateTest.php`

**Interfaces:**
- Consumes: `TaskController` (Task 3), `TaskRequest` (Task 3).
- Produces: `update(TaskRequest, Task): RedirectResponse` (updates attributes + re-associates subject/assignee when those keys are present), `destroy(Task): RedirectResponse` (soft delete). Routes `PUT /tasks/{task}` (`tasks.update`), `DELETE /tasks/{task}` (`tasks.destroy`). On update, `remind_at` is respected exactly as sent (including null = no reminder).

- [ ] **Step 1: Write the failing test**

`tests/Feature/TaskControllerUpdateTest.php`:
```php
<?php

use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\put;

beforeEach(fn () => actingAs(User::factory()->create()));

it('updates a task title and reassigns the assignee', function () {
    $task = Task::factory()->create(['title' => 'Old']);
    $assignee = User::factory()->create();

    put(route('tasks.update', $task), [
        'type' => $task->type->value,
        'title' => 'New title',
        'assignee_type' => 'user',
        'assignee_id' => $assignee->id,
    ])->assertRedirect();

    $task->refresh();
    expect($task->title)->toBe('New title')
        ->and($task->assignee->is($assignee))->toBeTrue();
});

it('respects an explicitly cleared reminder on update', function () {
    $task = Task::factory()->create(['remind_at' => now()->addDay()]);

    put(route('tasks.update', $task), [
        'type' => $task->type->value,
        'title' => $task->title,
        'due_at' => now()->addDays(2)->toDateTimeString(),
        'remind_at' => null,
    ])->assertRedirect();

    expect($task->fresh()->remind_at)->toBeNull();
});

it('soft deletes a task', function () {
    $task = Task::factory()->create();

    delete(route('tasks.destroy', $task))->assertRedirect();

    expect($task->fresh()->trashed())->toBeTrue();
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=TaskControllerUpdateTest`
Expected: FAIL (routes not defined).

- [ ] **Step 3: Add `update` and `destroy` to the controller**

Add these methods to `TaskController` (after `store`). Note `update` must NOT apply the create-time reminder default — it uses the raw validated value:
```php
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
```

- [ ] **Step 4: Add the routes**

In `routes/web.php`:
```php
Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
```

- [ ] **Step 5: Run to verify it passes**

Run: `php artisan test --compact --filter=TaskControllerUpdateTest`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/TaskController.php routes/web.php tests/Feature/TaskControllerUpdateTest.php
git commit -m "feat: update and delete tasks

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: `updateStatus` (complete/cancel/reopen) with optional note bridge

**Files:**
- Create: `app/Http/Requests/TaskStatusRequest.php`
- Modify: `app/Http/Controllers/TaskController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TaskStatusTest.php`

**Interfaces:**
- Consumes: `TaskController` (Task 3/4), `Task::markCompleted/markCanceled/reopen` (Task 1), `Note` model + `notes()` relation on subjects (existing).
- Produces: `TaskStatusRequest` validating `status` in `TaskStatus` values + `log_note` nullable string. `updateStatus(TaskStatusRequest, Task): RedirectResponse` dispatching to the right helper; when transitioning to `Completed` with a non-empty `log_note` and the task has a subject, creates a `Note` on that subject (`type` `'Follow Up'`, `title` `'Task completed: '.$task->title`, `content` = sanitized `log_note`). Route `PATCH /tasks/{task}/status` (`tasks.updateStatus`).

- [ ] **Step 1: Write the failing test**

`tests/Feature/TaskStatusTest.php`:
```php
<?php

use App\Enum\TaskStatus;
use App\Models\Company;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(fn () => actingAs(User::factory()->create()));

it('completes a task', function () {
    $task = Task::factory()->create();

    patch(route('tasks.updateStatus', $task), ['status' => 'Completed'])->assertRedirect();

    expect($task->fresh()->status)->toBe(TaskStatus::Completed)
        ->and($task->fresh()->completed_at)->not->toBeNull();
});

it('logs a note on the subject when completing with log_note', function () {
    $company = Company::factory()->create();
    $task = Task::factory()->forSubject($company)->create();

    patch(route('tasks.updateStatus', $task), [
        'status' => 'Completed',
        'log_note' => 'Spoke with the client, all good.',
    ])->assertRedirect();

    expect($company->notes()->count())->toBe(1)
        ->and(strip_tags($company->notes()->first()->content))->toContain('all good');
});

it('cancels and reopens a task', function () {
    $task = Task::factory()->completed()->create();

    patch(route('tasks.updateStatus', $task), ['status' => 'Open'])->assertRedirect();
    expect($task->fresh()->status)->toBe(TaskStatus::Open)
        ->and($task->fresh()->completed_at)->toBeNull();

    patch(route('tasks.updateStatus', $task), ['status' => 'Canceled'])->assertRedirect();
    expect($task->fresh()->status)->toBe(TaskStatus::Canceled);
});

it('rejects an invalid status', function () {
    $task = Task::factory()->create();
    patch(route('tasks.updateStatus', $task), ['status' => 'Nope'])
        ->assertSessionHasErrors('status');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=TaskStatusTest`
Expected: FAIL (route not defined).

- [ ] **Step 3: Create the status request**

`app/Http/Requests/TaskStatusRequest.php`:
```php
<?php

namespace App\Http\Requests;

use App\Enum\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'log_note' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Add `updateStatus` to the controller**

Add imports `use App\Enum\TaskStatus;`, `use App\Http\Requests\TaskStatusRequest;`, `use App\Services\NoteContentSanitizer;` and the method:
```php
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
```

- [ ] **Step 5: Add the route**

In `routes/web.php`:
```php
Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
```

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test --compact --filter=TaskStatusTest`
Expected: PASS.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/TaskStatusRequest.php app/Http/Controllers/TaskController.php routes/web.php tests/Feature/TaskStatusTest.php
git commit -m "feat: task status transitions with optional note bridge

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 6: Assignee and subject search endpoints

**Files:**
- Modify: `app/Http/Controllers/TaskController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TaskSearchTest.php`

**Interfaces:**
- Consumes: `TaskController` (Task 3), `User`/`Customer`/`Company`/`Lead` `withSearch` scopes + `withAvatar` (existing, used by `ActAsController`).
- Produces:
  - `assignees(Request): JsonResponse` — searches Users + Customers (`q` param), returns `[{type,id,full_name,avatar_url}]` (max 8 each). Route `GET /tasks/assignees` (`tasks.assignees`).
  - `subjects(Request): JsonResponse` — searches Companies + Customers + Leads (`q` param), returns `[{type,id,label}]` (max 8 each; `label` = `full_name ?? title`). Route `GET /tasks/subjects` (`tasks.subjects`).

- [ ] **Step 1: Write the failing test**

`tests/Feature/TaskSearchTest.php`:
```php
<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(fn () => actingAs(User::factory()->create()));

it('searches users and customers as assignees', function () {
    User::factory()->create(['first_name' => 'Zelda', 'last_name' => 'Tester']);
    Customer::factory()->create(['first_name' => 'Zelda', 'last_name' => 'Client']);

    $results = getJson(route('tasks.assignees', ['q' => 'Zelda']))->assertOk()->json();

    expect(collect($results)->pluck('type')->unique()->sort()->values()->all())
        ->toBe(['customer', 'user']);
});

it('searches companies, customers and leads as subjects', function () {
    Company::factory()->create(['title' => 'Searchable Co']);

    $results = getJson(route('tasks.subjects', ['q' => 'Searchable']))->assertOk()->json();

    expect(collect($results)->contains(fn ($r) => $r['type'] === 'company'))->toBeTrue();
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=TaskSearchTest`
Expected: FAIL (routes not defined).

- [ ] **Step 3: Add the search methods**

Add `use Illuminate\Http\JsonResponse;`, `use Illuminate\Http\Request;` and these methods to `TaskController`:
```php
    public function assignees(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->withAvatar()
            ->limit(8)
            ->get()
            ->map(fn (User $user): array => [
                'type' => 'user',
                'id' => $user->getKey(),
                'full_name' => $user->full_name,
                'avatar_url' => $user->avatar_url,
            ]);

        $customers = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->withAvatar()
            ->limit(8)
            ->get()
            ->map(fn (Customer $customer): array => [
                'type' => 'customer',
                'id' => $customer->getKey(),
                'full_name' => $customer->full_name,
                'avatar_url' => $customer->avatar_url,
            ]);

        return response()->json($users->concat($customers)->values());
    }

    public function subjects(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $companies = Company::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->limit(8)->get()
            ->map(fn (Company $company): array => ['type' => 'company', 'id' => $company->getKey(), 'label' => $company->title]);

        $customers = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->limit(8)->get()
            ->map(fn (Customer $customer): array => ['type' => 'customer', 'id' => $customer->getKey(), 'label' => $customer->full_name]);

        $leads = Lead::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->limit(8)->get()
            ->map(fn (Lead $lead): array => ['type' => 'lead', 'id' => $lead->getKey(), 'label' => $lead->title]);

        return response()->json($companies->concat($customers)->concat($leads)->values());
    }
```

- [ ] **Step 4: Add the routes (place BEFORE any `tasks/{task}` route to avoid conflicts)**

In `routes/web.php`, ensure these static paths come before the wildcard `tasks/{task}` routes:
```php
Route::get('tasks/assignees', [TaskController::class, 'assignees'])->name('tasks.assignees');
Route::get('tasks/subjects', [TaskController::class, 'subjects'])->name('tasks.subjects');
```

- [ ] **Step 5: Run to verify it passes**

Run: `php artisan test --compact --filter=TaskSearchTest`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/TaskController.php routes/web.php tests/Feature/TaskSearchTest.php
git commit -m "feat: assignee and subject search endpoints

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 7: `TaskReminder` notification + `SendTaskReminders` command + schedule

**Files:**
- Create: `app/Notifications/TaskReminder.php` (via artisan)
- Create: `app/Console/Commands/SendTaskReminders.php` (via artisan)
- Modify: `routes/console.php`
- Test: `tests/Feature/SendTaskRemindersTest.php`

**Interfaces:**
- Consumes: `Task` (Task 1), `TaskStatus` (Task 1).
- Produces:
  - `TaskReminder` notification (`via` = `['database']`) with `toDatabase` payload `{task_id, title, due_at, subject_label, url}`.
  - `SendTaskReminders` console command (signature `app:send-task-reminders`): selects `open` tasks where `remind_at <= now`, `reminded_at is null`, `assignee_id is not null`; notifies the assignee; stamps `reminded_at = now()`.
  - Scheduled entry `Schedule::command('app:send-task-reminders')->everyFiveMinutes();` in `routes/console.php`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/SendTaskRemindersTest.php`:
```php
<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Support\Facades\Notification;

it('notifies the assignee once for a due open task', function () {
    Notification::fake();
    $user = User::factory()->create();
    $task = Task::factory()->assignedTo($user)->create([
        'remind_at' => now()->subMinute(),
        'reminded_at' => null,
    ]);

    $this->artisan('app:send-task-reminders')->assertSuccessful();

    Notification::assertSentTo($user, TaskReminder::class);
    expect($task->fresh()->reminded_at)->not->toBeNull();
});

it('does not re-notify an already reminded task', function () {
    Notification::fake();
    $user = User::factory()->create();
    Task::factory()->assignedTo($user)->create([
        'remind_at' => now()->subHour(),
        'reminded_at' => now()->subMinute(),
    ]);

    $this->artisan('app:send-task-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

it('skips completed, future, and unassigned tasks', function () {
    Notification::fake();
    Task::factory()->completed()->create(['remind_at' => now()->subMinute()]);
    Task::factory()->create(['remind_at' => now()->addHour()]);
    Task::factory()->create(['remind_at' => now()->subMinute(), 'assignee_id' => null]);

    $this->artisan('app:send-task-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=SendTaskRemindersTest`
Expected: FAIL (notification + command classes missing).

- [ ] **Step 3: Generate and write the notification**

Run: `php artisan make:notification TaskReminder --no-interaction`

`app/Notifications/TaskReminder.php`:
```php
<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskReminder extends Notification
{
    use Queueable;

    public function __construct(public Task $task) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $subject = $this->task->subject;

        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'due_at' => $this->task->due_at?->toIso8601String(),
            'subject_label' => $subject ? ($subject->full_name ?? $subject->title ?? null) : null,
            'url' => $this->subjectUrl($subject),
        ];
    }

    protected function subjectUrl(?object $subject): string
    {
        return match (true) {
            $subject instanceof Customer => route('customers.show', $subject),
            $subject instanceof Company => route('companies.show', $subject),
            $subject instanceof Lead => route('leads.show', $subject),
            default => route('tasks.index'),
        };
    }
}
```

- [ ] **Step 4: Generate and write the command**

Run: `php artisan make:command SendTaskReminders --no-interaction`

`app/Console/Commands/SendTaskReminders.php`:
```php
<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'app:send-task-reminders';

    protected $description = 'Send in-app reminders for due, open, assigned tasks';

    public function handle(): int
    {
        Task::query()
            ->open()
            ->whereNotNull('remind_at')
            ->where('remind_at', '<=', now())
            ->whereNull('reminded_at')
            ->whereNotNull('assignee_id')
            ->with('assignee')
            ->chunkById(100, function ($tasks): void {
                foreach ($tasks as $task) {
                    $task->assignee?->notify(new TaskReminder($task));
                    $task->forceFill(['reminded_at' => now()])->save();
                }
            });

        return self::SUCCESS;
    }
}
```

> Note: the `assignee` (User/Customer) must use the `Notifiable` trait. `User` already does. If `Customer` does not, add `use Illuminate\Notifications\Notifiable;` and `use Notifiable;` to `app/Models/Customer.php` in this step (check first; the test uses a User assignee so it passes regardless, but customer reminders need it).

- [ ] **Step 5: Schedule the command**

In `routes/console.php`, add:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:send-task-reminders')->everyFiveMinutes();
```

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test --compact --filter=SendTaskRemindersTest`
Expected: PASS.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Notifications/TaskReminder.php app/Console/Commands/SendTaskReminders.php routes/console.php app/Models/Customer.php tests/Feature/SendTaskRemindersTest.php
git commit -m "feat: task reminders via scheduled notification command

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 8: `TaskController@index` (list groups + calendar feed + Inertia page)

**Files:**
- Modify: `app/Http/Controllers/TaskController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Tasks/Index.vue` (minimal placeholder; fleshed out in Task 11)
- Test: `tests/Feature/TaskIndexTest.php`

**Interfaces:**
- Consumes: `Task` scopes (Task 1), `TaskType`/`TaskStatus` `options()` (Task 1).
- Produces: `index(Request): Response` (Inertia) rendering `Tasks/Index` with props:
  - `overdue`, `due_today`, `upcoming` — arrays of serialized open tasks (each task includes `subject` and `assignee` eager-loaded) filtered to the current user by default unless `?scope=all`.
  - `calendar` — tasks with `due_at` in the requested range (`?start`/`?end` ISO dates; default current month) shaped as `[{id,title,start,allDay,status,type}]`.
  - `filters` — `{scope}`; `types` — `TaskType::options()`; `statuses` — `TaskStatus::options()`.
  - Route `GET /tasks` (`tasks.index`).

- [ ] **Step 1: Write the failing test**

`tests/Feature/TaskIndexTest.php`:
```php
<?php

use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the tasks index with grouped lists for the current user', function () {
    $user = User::factory()->create();
    actingAs($user);

    Task::factory()->assignedTo($user)->overdue()->create();
    Task::factory()->assignedTo($user)->dueToday()->create();
    Task::factory()->assignedTo($user)->create(['due_at' => now()->addWeek()]);
    Task::factory()->assignedTo(User::factory()->create())->overdue()->create(); // someone else's

    get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('overdue', 1)
            ->has('due_today', 1)
            ->has('upcoming', 1)
        );
});

it('returns a calendar feed for the requested range', function () {
    $user = User::factory()->create();
    actingAs($user);
    Task::factory()->assignedTo($user)->create(['due_at' => now()->startOfMonth()->addDays(2)]);

    get(route('tasks.index', [
        'start' => now()->startOfMonth()->toDateString(),
        'end' => now()->endOfMonth()->toDateString(),
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('calendar', 1));
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=TaskIndexTest`
Expected: FAIL (route/component missing).

- [ ] **Step 3: Add the index method**

Add `use App\Enum\TaskType;`, `use App\Enum\TaskStatus;`, `use Inertia\Inertia;`, `use Inertia\Response;`, `use Illuminate\Support\Carbon;` (if not already present) and:
```php
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $scope = $request->string('scope', 'mine')->toString();

        $base = fn () => Task::query()
            ->with(['subject', 'assignee'])
            ->when($scope !== 'all' && $user !== null, fn ($query) => $query->forAssignee($user));

        $start = $request->filled('start') ? Carbon::parse($request->string('start')->toString())->startOfDay() : now()->startOfMonth();
        $end = $request->filled('end') ? Carbon::parse($request->string('end')->toString())->endOfDay() : now()->endOfMonth();

        return Inertia::render('Tasks/Index', [
            'overdue' => $base()->overdue()->orderBy('due_at')->get(),
            'due_today' => $base()->open()->dueOn(now())->orderBy('due_at')->get(),
            'upcoming' => $base()->open()->whereNotNull('due_at')->where('due_at', '>', now())->orderBy('due_at')->limit(50)->get(),
            'calendar' => $base()->between($start, $end)->get()->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->due_at?->toIso8601String(),
                'allDay' => $task->all_day,
                'status' => $task->status->value,
                'type' => $task->type->value,
            ]),
            'filters' => ['scope' => $scope],
            'types' => TaskType::options(),
            'statuses' => TaskStatus::options(),
        ]);
    }
```

- [ ] **Step 4: Add the route (before `tasks/{task}` wildcards)**

```php
Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
```

- [ ] **Step 5: Create a minimal page so Inertia can resolve the component**

`resources/js/Pages/Tasks/Index.vue`:
```vue
<script setup>
defineProps({
  overdue: { type: Array, default: () => [] },
  due_today: { type: Array, default: () => [] },
  upcoming: { type: Array, default: () => [] },
  calendar: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  types: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
})
</script>

<template>
  <div>
    <h1 class="text-xl font-bold">My Activities</h1>
  </div>
</template>
```

- [ ] **Step 6: Run to verify it passes**

Run: `php artisan test --compact --filter=TaskIndexTest`
Expected: PASS.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/TaskController.php routes/web.php resources/js/Pages/Tasks/Index.vue tests/Feature/TaskIndexTest.php
git commit -m "feat: tasks index with grouped lists and calendar feed

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 9: `TaskSeeder`

**Files:**
- Create: `database/seeders/TaskSeeder.php` (via artisan)
- Modify: `database/seeders/DatabaseSeeder.php` (call `TaskSeeder` after existing record seeders)
- Test: `tests/Feature/TaskSeederTest.php`

**Interfaces:**
- Consumes: `TaskFactory` (Task 1), existing Company/Customer/Lead/User records.
- Produces: `TaskSeeder` that creates a spread of tasks (overdue / due-today / upcoming / completed) across a sample of existing subjects, assigned to existing users — referencing only existing records, doing nothing when none exist.

- [ ] **Step 1: Write the failing test**

`tests/Feature/TaskSeederTest.php`:
```php
<?php

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\TaskSeeder;

it('seeds tasks against existing records without creating users', function () {
    User::factory()->count(2)->create();
    Company::factory()->count(2)->create();

    $users_before = User::count();
    (new TaskSeeder())->run();

    expect(Task::count())->toBeGreaterThan(0)
        ->and(User::count())->toBe($users_before);
});

it('does nothing when there are no records to attach to', function () {
    (new TaskSeeder())->run();
    expect(Task::count())->toBe(0);
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=TaskSeederTest`
Expected: FAIL (class missing).

- [ ] **Step 3: Generate and write the seeder**

Run: `php artisan make:seeder TaskSeeder --no-interaction`

`database/seeders/TaskSeeder.php`:
```php
<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $assignee_ids = User::pluck('id');

        if ($assignee_ids->isEmpty()) {
            return;
        }

        $subjects = collect()
            ->concat(Company::inRandomOrder()->limit(5)->get())
            ->concat(Customer::inRandomOrder()->limit(5)->get())
            ->concat(Lead::inRandomOrder()->limit(5)->get());

        if ($subjects->isEmpty()) {
            return;
        }

        foreach ($subjects as $subject) {
            foreach (['overdue', 'dueToday', 'upcoming', 'completed'] as $bucket) {
                $factory = Task::factory()
                    ->forSubject($subject)
                    ->assignedTo(User::find($assignee_ids->random()));

                $factory = match ($bucket) {
                    'overdue' => $factory->overdue(),
                    'dueToday' => $factory->dueToday(),
                    'completed' => $factory->completed(),
                    default => $factory,
                };

                $factory->create();
            }
        }
    }
}
```

- [ ] **Step 4: Register the seeder**

In `database/seeders/DatabaseSeeder.php`, add `$this->call(TaskSeeder::class);` after the company/customer/lead seeders.

- [ ] **Step 5: Run to verify it passes**

Run: `php artisan test --compact --filter=TaskSeederTest`
Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/seeders/TaskSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/TaskSeederTest.php
git commit -m "feat: task seeder over existing records

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 10: Install FullCalendar

**Files:**
- Modify: `package.json`, `package-lock.json`

**Interfaces:**
- Produces: FullCalendar packages available for import in Task 12.

- [ ] **Step 1: Install the packages**

Run:
```bash
npm install @fullcalendar/core @fullcalendar/vue3 @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/interaction
```

- [ ] **Step 2: Verify the build still compiles**

Run: `npm run build`
Expected: build succeeds with no missing-module errors.

- [ ] **Step 3: Commit**

```bash
git add package.json package-lock.json
git commit -m "build: add FullCalendar dependencies

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 11: Task components + record-page section (TasksSection embedded on the 3 Show pages)

**Files:**
- Create: `resources/js/Components/Tasks/AssigneePicker.vue`
- Create: `resources/js/Components/Tasks/SubjectPicker.vue`
- Create: `resources/js/Components/Tasks/TaskForm.vue`
- Create: `resources/js/Components/Tasks/TaskItem.vue`
- Create: `resources/js/Components/Tasks/TaskStatusToggle.vue`
- Create: `resources/js/Components/Tasks/TasksSection.vue`
- Modify: `app/Http/Controllers/CompanyController.php`, `CustomerController.php`, `LeadController.php` (pass `tasks`, `task_types`, `task_statuses` props to their `show` views)
- Modify: `resources/js/Pages/Company/Show.vue`, `resources/js/Pages/Customers/Show.vue`, `resources/js/Pages/Leads/Show.vue` (embed `TasksSection`)
- Test: `tests/Feature/RecordShowTasksTest.php`

**Interfaces:**
- Consumes: `tasks.store`/`tasks.update`/`tasks.destroy`/`tasks.updateStatus`/`tasks.assignees` routes (Tasks 3–6); `paginatedTasks()` (Task 2); shadcn-vue UI components; `useConfirm` composable; `CreatedBy`, `StatusBadge`, `Avatar`, `Pagination`.
- Produces: a reusable `TasksSection` accepting `subject_type` (`'company'|'customer'|'lead'`), `subject_id`, `tasks` (paginator), `types` (array), `statuses` (array).

- [ ] **Step 1: Write the failing test (controllers must expose task props on show)**

`tests/Feature/RecordShowTasksTest.php`:
```php
<?php

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(fn () => actingAs(User::factory()->create()));

it('passes paginated tasks and option lists to the company show page', function () {
    $company = Company::factory()->create();
    Task::factory()->forSubject($company)->count(2)->create();

    get(route('companies.show', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 2)
            ->has('task_types')
            ->has('task_statuses')
        );
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter=RecordShowTasksTest`
Expected: FAIL (props missing).

- [ ] **Step 3: Add task props to the three `show` controllers**

In each of `CompanyController@show`, `CustomerController@show`, `LeadController@show`, add to the `Inertia::render(...)` props array (using the existing `$model` variable name in each — e.g. `$company`):
```php
            'tasks' => $company->paginatedTasks(),
            'task_types' => \App\Enum\TaskType::options(),
            'task_statuses' => \App\Enum\TaskStatus::options(),
```
(Use `$customer` / `$lead` respectively. Import the enums at the top instead of FQCN if preferred, matching each file's style.)

- [ ] **Step 4: Create `AssigneePicker.vue`** (debounced type-ahead, mirrors `ActAsSwitcher`)

`resources/js/Components/Tasks/AssigneePicker.vue`:
```vue
<script setup>
import { ref, watch } from 'vue'
import Avatar from '@/Components/Avatar.vue'

const props = defineProps({
  modelType: { type: String, default: null },
  modelId: { type: [Number, String], default: null },
  initialLabel: { type: String, default: '' },
})
const emit = defineEmits(['update:modelType', 'update:modelId'])

const query = ref(props.initialLabel)
const results = ref([])
const open = ref(false)
let debounce = null

const search = async (term) => {
  const res = await fetch(route('tasks.assignees', { q: term ?? '' }), { headers: { Accept: 'application/json' } })
  results.value = await res.json()
}

watch(query, (term) => {
  clearTimeout(debounce)
  open.value = true
  debounce = setTimeout(() => search(term), 200)
})

const choose = (person) => {
  emit('update:modelType', person.type)
  emit('update:modelId', person.id)
  query.value = person.full_name
  open.value = false
}

const clear = () => {
  emit('update:modelType', null)
  emit('update:modelId', null)
  query.value = ''
}
</script>

<template>
  <div class="relative">
    <input
        v-model="query"
        type="text"
        placeholder="Search users and customers..."
        class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
        @focus="open = true"
    >
    <button v-if="query" type="button" class="absolute right-2 top-2 text-xs text-muted-foreground" @click="clear">clear</button>
    <ul v-if="open && results.length" class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-border bg-popover p-1 shadow-lg">
      <li v-for="person in results" :key="`${person.type}:${person.id}`">
        <button type="button" class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-muted" @click="choose(person)">
          <Avatar :name="person.full_name" :src="person.avatar_url" size="xs" />
          <span class="truncate">{{ person.full_name }}</span>
          <span class="ml-auto text-xs text-muted-foreground capitalize">{{ person.type }}</span>
        </button>
      </li>
    </ul>
  </div>
</template>
```

- [ ] **Step 5: Create `SubjectPicker.vue`** (same pattern, hits `tasks.subjects`, optional — hidden when the section already has a fixed subject)

`resources/js/Components/Tasks/SubjectPicker.vue`:
```vue
<script setup>
import { ref, watch } from 'vue'

defineProps({ initialLabel: { type: String, default: '' } })
const emit = defineEmits(['update:modelType', 'update:modelId'])

const query = ref('')
const results = ref([])
const open = ref(false)
let debounce = null

const search = async (term) => {
  const res = await fetch(route('tasks.subjects', { q: term ?? '' }), { headers: { Accept: 'application/json' } })
  results.value = await res.json()
}
watch(query, (term) => {
  clearTimeout(debounce)
  open.value = true
  debounce = setTimeout(() => search(term), 200)
})
const choose = (subject) => {
  emit('update:modelType', subject.type)
  emit('update:modelId', subject.id)
  query.value = subject.label
  open.value = false
}
</script>

<template>
  <div class="relative">
    <input v-model="query" type="text" placeholder="Search companies, customers, leads..."
           class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
           @focus="open = true">
    <ul v-if="open && results.length" class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-border bg-popover p-1 shadow-lg">
      <li v-for="subject in results" :key="`${subject.type}:${subject.id}`">
        <button type="button" class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-muted" @click="choose(subject)">
          <span class="truncate">{{ subject.label }}</span>
          <span class="ml-auto text-xs text-muted-foreground capitalize">{{ subject.type }}</span>
        </button>
      </li>
    </ul>
  </div>
</template>
```

- [ ] **Step 6: Create `TaskForm.vue`** (create/edit modal-style card mirroring `NoteForm`)

`resources/js/Components/Tasks/TaskForm.vue`:
```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import { Label } from '@/Components/UI/label/index.js'
import { Input } from '@/Components/UI/input/index.js'
import { Button } from '@/Components/UI/button/index.js'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/UI/select/index.js'
import { Card, CardContent } from '@/Components/UI/card/index.js'
import AssigneePicker from '@/Components/Tasks/AssigneePicker.vue'

const props = defineProps({
  subject_type: { type: String, default: null },
  subject_id: { type: [Number, String], default: null },
  task: { type: Object, default: null },
  types: { type: Array, default: () => [] },
})
const emit = defineEmits(['saved', 'cancel'])

const form = useForm({
  subject_type: props.subject_type,
  subject_id: props.subject_id,
  assignee_type: props.task?.assignee_type_key || null,
  assignee_id: props.task?.assignee?.id || null,
  type: props.task?.type || props.types[0]?.value || 'To-Do',
  title: props.task?.title || '',
  description: props.task?.description || '',
  due_at: props.task?.due_at ? props.task.due_at.slice(0, 16) : '',
  all_day: props.task?.all_day || false,
  remind_at: props.task?.remind_at ? props.task.remind_at.slice(0, 16) : '',
})

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      if (!props.task) { form.reset('title', 'description', 'due_at', 'remind_at') }
      emit('saved')
    },
  }
  if (props.task) {
    form.put(route('tasks.update', props.task.id), options)
  } else {
    form.post(route('tasks.store'), options)
  }
}
</script>

<template>
  <Card>
    <CardContent>
      <form class="space-y-3" @submit.prevent="submit">
        <div class="grid gap-3 sm:grid-cols-3">
          <div class="space-y-2 sm:col-span-2">
            <Label for="task_title">Title</Label>
            <Input id="task_title" v-model="form.title" type="text" placeholder="Task title" />
            <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
          </div>
          <div class="space-y-2">
            <Label for="task_type">Type</Label>
            <Select v-model="form.type">
              <SelectTrigger id="task_type" class="w-full"><SelectValue placeholder="Type" /></SelectTrigger>
              <SelectContent>
                <SelectItem v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <div class="space-y-2">
            <Label>Assignee</Label>
            <AssigneePicker
                :initial-label="task?.assignee?.full_name || ''"
                @update:model-type="form.assignee_type = $event"
                @update:model-id="form.assignee_id = $event"
            />
          </div>
          <div class="space-y-2">
            <Label for="task_due">Due</Label>
            <Input id="task_due" v-model="form.due_at" :type="form.all_day ? 'date' : 'datetime-local'" />
            <label class="flex items-center gap-2 text-sm">
              <input v-model="form.all_day" type="checkbox"> All day
            </label>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="task_remind">Remind at (optional)</Label>
          <Input id="task_remind" v-model="form.remind_at" type="datetime-local" />
        </div>

        <div class="flex justify-end gap-3">
          <Button type="button" variant="ghost" size="sm" @click="emit('cancel')">Cancel</Button>
          <Button type="submit" size="sm" :disabled="form.processing">{{ task ? 'Update Task' : 'Save Task' }}</Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
```

- [ ] **Step 7: Create `TaskStatusToggle.vue`**

`resources/js/Components/Tasks/TaskStatusToggle.vue`:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/Components/UI/button/index.js'
import { Check, RotateCcw, X } from 'lucide-vue-next'

const props = defineProps({ task: { type: Object, required: true } })
const logging = ref(false)
const logNote = ref('')

const setStatus = (status, extra = {}) => {
  router.patch(route('tasks.updateStatus', props.task.id), { status, ...extra }, { preserveScroll: true })
}

const complete = () => {
  if (logging.value) {
    setStatus('Completed', { log_note: logNote.value })
    logging.value = false
    logNote.value = ''
  } else {
    logging.value = true
  }
}
</script>

<template>
  <div class="flex items-center gap-2">
    <template v-if="task.status === 'Open'">
      <div v-if="logging" class="flex items-center gap-2">
        <input v-model="logNote" type="text" placeholder="Log a note (optional)"
               class="rounded-md border border-border bg-background px-2 py-1 text-sm">
      </div>
      <Button size="icon-sm" title="Complete" aria-label="Complete" @click="complete"><Check /></Button>
      <Button size="icon-sm" variant="ghost" title="Cancel task" aria-label="Cancel task" @click="setStatus('Canceled')"><X /></Button>
    </template>
    <Button v-else size="icon-sm" variant="ghost" title="Reopen" aria-label="Reopen" @click="setStatus('Open')"><RotateCcw /></Button>
  </div>
</template>
```

- [ ] **Step 8: Create `TaskItem.vue`** (mirrors `NoteItem`)

`resources/js/Components/Tasks/TaskItem.vue`:
```vue
<script setup>
import { computed } from 'vue'
import { Button } from '@/Components/UI/button/index.js'
import { Pencil, Trash2 } from 'lucide-vue-next'
import StatusBadge from '@/Components/StatusBadge.vue'
import Avatar from '@/Components/Avatar.vue'
import TaskStatusToggle from '@/Components/Tasks/TaskStatusToggle.vue'

const props = defineProps({ task: { type: Object, required: true } })
defineEmits(['edit', 'remove'])

const overdue = computed(() =>
  props.task.status === 'Open' && props.task.due_at && new Date(props.task.due_at) < new Date(),
)
const due_label = computed(() => {
  if (!props.task.due_at) { return 'No due date' }
  const d = new Date(props.task.due_at)
  return props.task.all_day ? d.toLocaleDateString() : d.toLocaleString()
})
</script>

<template>
  <div class="rounded-md border border-border p-3">
    <div class="flex items-center justify-between gap-2">
      <h3 class="font-bold" :class="{ 'line-through text-muted-foreground': task.status !== 'Open' }">{{ task.title }}</h3>
      <StatusBadge :status="task.type" />
    </div>
    <p v-if="task.description" class="mt-1 text-sm text-muted-foreground">{{ task.description }}</p>
    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
      <span :class="overdue ? 'font-semibold text-destructive' : 'text-muted-foreground'">{{ due_label }}</span>
      <span v-if="task.assignee" class="flex items-center gap-1">
        <Avatar :name="task.assignee.full_name" :src="task.assignee.avatar_url" size="xs" />
        {{ task.assignee.full_name }}
      </span>
      <TaskStatusToggle :task="task" />
    </div>
    <div class="mt-2 flex justify-end gap-2">
      <Button size="icon-sm" title="Edit" aria-label="Edit" @click="$emit('edit', task)"><Pencil /></Button>
      <Button size="icon-sm" variant="ghost" class="text-destructive hover:text-destructive" title="Remove" aria-label="Remove" @click="$emit('remove', task)"><Trash2 /></Button>
    </div>
  </div>
</template>
```

- [ ] **Step 9: Create `TasksSection.vue`** (mirrors `Components/Notes/NotesSection`)

`resources/js/Components/Tasks/TasksSection.vue`:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/Components/UI/button/index.js'
import { Plus } from 'lucide-vue-next'
import Pagination from '@/Components/Pagination.vue'
import TaskItem from '@/Components/Tasks/TaskItem.vue'
import TaskForm from '@/Components/Tasks/TaskForm.vue'
import { useConfirm } from '@/composables/useConfirm.js'

const props = defineProps({
  subject_type: { type: String, required: true },
  subject_id: { type: [Number, String], required: true },
  tasks: { type: Object, required: true },
  types: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
})

const showForm = ref(false)
const editing = ref(null)
const confirm = useConfirm()

const onSaved = () => { showForm.value = false; editing.value = null }
const startEdit = (task) => { editing.value = task; showForm.value = false }

const remove = async (task) => {
  if (await confirm({ title: 'Delete task?', message: `Delete "${task.title}"?` })) {
    router.delete(route('tasks.destroy', task.id), { preserveScroll: true })
  }
}
</script>

<template>
  <section class="space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold">Tasks</h2>
      <Button size="icon-sm" aria-label="Add Task" title="Add Task" @click="showForm = !showForm; editing = null"><Plus /></Button>
    </div>

    <TaskForm
        v-if="showForm"
        :subject_type="subject_type"
        :subject_id="subject_id"
        :types="types"
        @saved="onSaved"
        @cancel="showForm = false"
    />

    <div class="space-y-2">
      <template v-for="task in tasks.data" :key="task.id">
        <TaskForm
            v-if="editing && editing.id === task.id"
            :subject_type="subject_type"
            :subject_id="subject_id"
            :task="task"
            :types="types"
            @saved="onSaved"
            @cancel="editing = null"
        />
        <TaskItem v-else :task="task" @edit="startEdit" @remove="remove" />
      </template>
      <p v-if="!tasks.data.length" class="text-sm text-muted-foreground">No tasks yet.</p>
    </div>

    <Pagination v-if="tasks.last_page > 1" :links="tasks.links" />
  </section>
</template>
```

- [ ] **Step 10: Embed `TasksSection` on the three Show pages**

In `resources/js/Pages/Company/Show.vue`, `Customers/Show.vue`, `Leads/Show.vue`: import the component and place it near the existing Notes section. Add to `<script setup>`:
```js
import TasksSection from '@/Components/Tasks/TasksSection.vue'
```
Add to the props (`defineProps`): `tasks`, `task_types`, `task_statuses`. Then in the template, beside the Notes section:
```vue
<TasksSection
    :subject_type="'company'"
    :subject_id="company.id"
    :tasks="tasks"
    :types="task_types"
    :statuses="task_statuses"
/>
```
(Use `'customer'`/`customer.id` and `'lead'`/`lead.id` on the respective pages — match the prop name each page already uses for its record.)

- [ ] **Step 11: Run the feature test + build**

Run: `php artisan test --compact --filter=RecordShowTasksTest`
Expected: PASS.
Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 12: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CompanyController.php app/Http/Controllers/CustomerController.php app/Http/Controllers/LeadController.php resources/js/Components/Tasks resources/js/Pages/Company/Show.vue resources/js/Pages/Customers/Show.vue resources/js/Pages/Leads/Show.vue tests/Feature/RecordShowTasksTest.php
git commit -m "feat: tasks section on company, customer, lead pages

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 12: Tasks index page (List + FullCalendar), nav link, dashboard widget

**Files:**
- Create: `resources/js/Components/Tasks/TaskCalendar.vue`
- Rewrite: `resources/js/Pages/Tasks/Index.vue`
- Modify: the main nav component (the one rendering existing links such as Customers/Companies/Leads — locate via `grep -rl "leads.index" resources/js`)
- Modify: `resources/js/Pages/Home.vue` (dashboard widget) and `app/Http/Controllers/` Home action (locate the controller/closure rendering `Home`) to pass `my_tasks`
- Test: `tests/Browser/TasksPageTest.php` (Pest browser smoke) + extend `tests/Feature/TaskIndexTest.php` if a Home prop is added

**Interfaces:**
- Consumes: `tasks.index` props (Task 8); FullCalendar (Task 10); `TaskForm`, `TaskItem` (Task 11); `tasks.update` for drag-reschedule.
- Produces: a working List ↔ Calendar page and a dashboard "My activities" widget.

- [ ] **Step 1: Create `TaskCalendar.vue`**

`resources/js/Components/Tasks/TaskCalendar.vue`:
```vue
<script setup>
import { computed } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const props = defineProps({ events: { type: Array, default: () => [] } })
const emit = defineEmits(['reschedule', 'select-date', 'open-event'])

const options = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
  editable: true,
  events: props.events,
  eventDrop: (info) => emit('reschedule', { id: info.event.id, due_at: info.event.start?.toISOString(), allDay: info.event.allDay }),
  dateClick: (info) => emit('select-date', info.dateStr),
  eventClick: (info) => emit('open-event', info.event.id),
}))
</script>

<template>
  <FullCalendar :options="options" />
</template>
```

- [ ] **Step 2: Rewrite `Pages/Tasks/Index.vue`** (list + calendar toggle; drag-reschedule posts to `tasks.update`)

`resources/js/Pages/Tasks/Index.vue`:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/Components/UI/button/index.js'
import TaskItem from '@/Components/Tasks/TaskItem.vue'
import TaskForm from '@/Components/Tasks/TaskForm.vue'
import TaskCalendar from '@/Components/Tasks/TaskCalendar.vue'
import { useConfirm } from '@/composables/useConfirm.js'

const props = defineProps({
  overdue: { type: Array, default: () => [] },
  due_today: { type: Array, default: () => [] },
  upcoming: { type: Array, default: () => [] },
  calendar: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  types: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
})

const view = ref('list')
const showForm = ref(false)
const confirm = useConfirm()

const reschedule = ({ id, due_at }) => {
  const task = [...props.overdue, ...props.due_today, ...props.upcoming].find((t) => String(t.id) === String(id))
  router.put(route('tasks.update', id), {
    type: task?.type || 'To-Do',
    title: task?.title || 'Task',
    due_at,
  }, { preserveScroll: true })
}

const remove = async (task) => {
  if (await confirm({ title: 'Delete task?', message: `Delete "${task.title}"?` })) {
    router.delete(route('tasks.destroy', task.id), { preserveScroll: true })
  }
}

const reloadRange = (info) => {
  router.reload({ data: { start: info.startStr, end: info.endStr }, only: ['calendar'] })
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">My Activities</h1>
      <div class="flex gap-2">
        <Button variant="secondary" size="sm" @click="view = view === 'list' ? 'calendar' : 'list'">
          {{ view === 'list' ? 'Calendar' : 'List' }}
        </Button>
        <Button size="sm" @click="showForm = !showForm">New Task</Button>
      </div>
    </div>

    <TaskForm v-if="showForm" :types="types" @saved="showForm = false" @cancel="showForm = false" />

    <div v-if="view === 'list'" class="space-y-6">
      <section v-if="overdue.length">
        <h2 class="mb-2 font-bold text-destructive">Overdue</h2>
        <div class="space-y-2"><TaskItem v-for="t in overdue" :key="t.id" :task="t" @remove="remove" @edit="() => {}" /></div>
      </section>
      <section>
        <h2 class="mb-2 font-bold">Due Today</h2>
        <div class="space-y-2"><TaskItem v-for="t in due_today" :key="t.id" :task="t" @remove="remove" @edit="() => {}" /></div>
        <p v-if="!due_today.length" class="text-sm text-muted-foreground">Nothing due today.</p>
      </section>
      <section>
        <h2 class="mb-2 font-bold">Upcoming</h2>
        <div class="space-y-2"><TaskItem v-for="t in upcoming" :key="t.id" :task="t" @remove="remove" @edit="() => {}" /></div>
        <p v-if="!upcoming.length" class="text-sm text-muted-foreground">Nothing upcoming.</p>
      </section>
    </div>

    <TaskCalendar v-else :events="calendar" @reschedule="reschedule" />
  </div>
</template>
```
> Note: the calendar range reload (`reloadRange`) can be wired to FullCalendar's `datesSet` callback in a follow-up; the default month feed already renders. Keep `reloadRange` exported for that wiring or remove if unused to satisfy lint.

- [ ] **Step 3: Add a nav link to `tasks.index`**

Find the nav: `grep -rl "leads.index" resources/js`. In that component, add a link beside the existing ones (match the existing `<Link>` markup exactly):
```vue
<Link :href="route('tasks.index')" class="...same classes as siblings...">Tasks</Link>
```

- [ ] **Step 4: Add the dashboard widget**

In the action rendering `Home` (find it: `grep -rn "Inertia::render('Home'" app routes`), add a prop:
```php
            'my_tasks' => auth()->check()
                ? \App\Models\Task::query()->with('subject')->forAssignee(auth()->user())->overdue()->orderBy('due_at')->limit(5)->get()
                : [],
```
In `resources/js/Pages/Home.vue`, add `my_tasks` to `defineProps` and render a small list:
```vue
<section class="rounded-lg border border-border p-4">
  <h2 class="mb-2 font-bold">My Overdue Tasks</h2>
  <ul class="space-y-1 text-sm">
    <li v-for="t in my_tasks" :key="t.id" class="flex justify-between">
      <span>{{ t.title }}</span>
      <span class="text-destructive">{{ new Date(t.due_at).toLocaleDateString() }}</span>
    </li>
    <li v-if="!my_tasks.length" class="text-muted-foreground">No overdue tasks 🎉</li>
  </ul>
</section>
```

- [ ] **Step 5: Write a Pest browser smoke test**

`tests/Browser/TasksPageTest.php`:
```php
<?php

use App\Models\Task;
use App\Models\User;

it('renders the tasks page without console errors', function () {
    $user = User::factory()->create();
    Task::factory()->assignedTo($user)->dueToday()->create();

    $page = visit(route('tasks.index'))->actingAs($user);

    $page->assertSee('My Activities')->assertNoJavascriptErrors();
});
```
> If the project's browser tests use a different `actingAs`/visit order, match the existing `tests/Browser` examples.

- [ ] **Step 6: Build and run tests**

Run: `npm run build`
Expected: succeeds (FullCalendar imports resolve).
Run: `php artisan test --compact --filter=TasksPageTest`
Expected: PASS (or skipped if the browser driver is unavailable in CI — then run `php artisan test --compact --filter=TaskIndexTest` to confirm no regressions).

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/js/Components/Tasks/TaskCalendar.vue resources/js/Pages/Tasks/Index.vue resources/js/Pages/Home.vue app routes tests/Browser/TasksPageTest.php
git commit -m "feat: tasks index list+calendar, nav link, dashboard widget

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Final verification

- [ ] Run the full suite: `php artisan test --compact`
- [ ] Run `vendor/bin/pint --dirty --format agent` once more
- [ ] Run `npm run build`
- [ ] Manually: create a task on a Company page, assign it, complete it with a logged note (verify a Note appears), view it on the calendar and drag to reschedule, confirm the reminder command sends an in-app notification (`php artisan app:send-task-reminders` after setting a `remind_at` in the past).

## Self-Review Notes (addressed)

- **Spec coverage:** data model (T1), WithTasks/embeds (T2/T11), CRUD (T3/T4), status + note bridge (T5), pickers/search (T6), reminders + schedule (T7), index list+calendar feed (T8), seeder (T9), FullCalendar dep (T10), components + record sections (T11), index page + nav + dashboard (T12). All spec sections map to a task.
- **Customer Notifiable:** flagged in T7 step 4 (Customer must use `Notifiable` for customer-assignee reminders).
- **Route ordering:** static `tasks/assignees`, `tasks/subjects`, `tasks` declared before `tasks/{task}` wildcards (T6/T8).
- **remind_at semantics:** default-on-create only (T3); update respects explicit null (T4).
- **Naming:** `Task` model throughout; no collision with Spatie activity log.
