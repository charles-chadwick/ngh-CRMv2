# Leads CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver full CRUD for the `Lead` model plus Notes and created/updated-by attribution parity with Customer/Company.

**Architecture:** Mirror the existing resource pattern (controller + form request + Inertia Vue pages). `status`/`type` are PHP string enums (cast on the model) so they can later become DB relations with a localized change. `assigned_at` is auto-managed by a model `saving` hook. Notes/attribution reuse the existing `WithNotes`/`WithActivity` traits and Vue components.

**Tech Stack:** Laravel 13, PHP 8.4, Inertia v3, Vue 3, Tailwind v4, Pest 4, Spatie Activitylog.

## Global Constraints

- Naming: variables `snake_case`, methods/functions `camelCase`, classes `TitleCase` (enum keys TitleCase).
- PHP: curly braces on all control structures; constructor property promotion; explicit return types and param type hints; PHPDoc over inline comments.
- Enums live in `app/Enum/` (singular), namespace `App\Enum`, backed string enum with a static `options(): list<array{value,label}>` helper (match `App\Enum\UserRole`).
- Mass assignment uses the PHP `#[Fillable([...])]` attribute (match `App\Models\Customer`).
- Run `vendor/bin/pint --dirty --format agent` after editing PHP, before each commit.
- Run tests with `php artisan test --compact`.
- Follow existing sibling conventions; reuse components before creating new ones.

---

### Task 1: Lead enums

**Files:**
- Create: `app/Enum/LeadStatus.php`
- Create: `app/Enum/LeadType.php`
- Test: `tests/Unit/LeadEnumTest.php`

**Interfaces:**
- Produces: `App\Enum\LeadStatus` (cases `New`, `Working`, `Qualified`, `Closed`; values identical to names) and `App\Enum\LeadType` (cases `Web`, `Phone`, `Email`, `Event`, `Other`), each with `public static function options(): array` returning `list<array{value: string, label: string}>`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/LeadEnumTest.php`:

```php
<?php

use App\Enum\LeadStatus;
use App\Enum\LeadType;

it('exposes the lead status values', function () {
    expect(array_map(fn (LeadStatus $s) => $s->value, LeadStatus::cases()))
        ->toBe(['New', 'Working', 'Qualified', 'Closed']);
});

it('exposes the lead type values', function () {
    expect(array_map(fn (LeadType $t) => $t->value, LeadType::cases()))
        ->toBe(['Web', 'Phone', 'Email', 'Event', 'Other']);
});

it('builds dropdown options for statuses', function () {
    expect(LeadStatus::options())->toBe([
        ['value' => 'New', 'label' => 'New'],
        ['value' => 'Working', 'label' => 'Working'],
        ['value' => 'Qualified', 'label' => 'Qualified'],
        ['value' => 'Closed', 'label' => 'Closed'],
    ]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=LeadEnumTest`
Expected: FAIL — `Class "App\Enum\LeadStatus" not found`.

- [ ] **Step 3: Create the enums**

Create `app/Enum/LeadStatus.php`:

```php
<?php

namespace App\Enum;

enum LeadStatus: string
{
    case New = 'New';
    case Working = 'Working';
    case Qualified = 'Qualified';
    case Closed = 'Closed';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->name,
        ], self::cases());
    }
}
```

Create `app/Enum/LeadType.php`:

```php
<?php

namespace App\Enum;

enum LeadType: string
{
    case Web = 'Web';
    case Phone = 'Phone';
    case Email = 'Email';
    case Event = 'Event';
    case Other = 'Other';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->name,
        ], self::cases());
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=LeadEnumTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enum/LeadStatus.php app/Enum/LeadType.php tests/Unit/LeadEnumTest.php
git commit -m "Add LeadStatus and LeadType enums"
```

---

### Task 2: Lead model and factory

**Files:**
- Modify: `app/Models/Lead.php`
- Modify: `database/factories/LeadFactory.php`
- Test: `tests/Feature/LeadModelTest.php`

**Interfaces:**
- Consumes: `App\Enum\LeadStatus`, `App\Enum\LeadType` (Task 1).
- Produces: `Lead` fillable `['title', 'type', 'status', 'assigned_to', 'initiated_at']`; casts `status => LeadStatus`, `type => LeadType`, `assigned_at`/`initiated_at` => datetime; traits `WithActivity`, `WithNotes`, `WithSearch`, `WithSort`; `scopeWithSearch($query, $search)`, `scopeWithSort($query, ?string $sort_by, ?string $sort_direction)`; auto-managed `assigned_at`. `LeadFactory` produces valid enum-backed `status`/`type`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LeadModelTest.php`:

```php
<?php

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts status and type to enums', function () {
    $lead = Lead::factory()->create();

    expect($lead->status)->toBeInstanceOf(LeadStatus::class)
        ->and($lead->type)->toBeInstanceOf(LeadType::class);
});

it('mass-assigns the fillable lead attributes', function () {
    $user = User::factory()->create();

    $lead = Lead::create([
        'title' => 'Roof replacement',
        'type' => LeadType::Web,
        'status' => LeadStatus::New,
        'assigned_to' => $user->id,
        'initiated_at' => now(),
    ]);

    expect($lead->title)->toBe('Roof replacement')
        ->and($lead->assigned_to)->toBe($user->id);
});

it('sets assigned_at when a lead is assigned', function () {
    $user = User::factory()->create();

    $lead = Lead::factory()->create(['assigned_to' => null]);
    expect($lead->assigned_at)->toBeNull();

    $lead->update(['assigned_to' => $user->id]);

    expect($lead->fresh()->assigned_at)->not->toBeNull();
});

it('clears assigned_at when a lead is unassigned', function () {
    $lead = Lead::factory()->create(['assigned_to' => User::factory()]);
    expect($lead->assigned_at)->not->toBeNull();

    $lead->update(['assigned_to' => null]);

    expect($lead->fresh()->assigned_at)->toBeNull();
});

it('searches leads by normalized title', function () {
    Lead::factory()->create(['title' => 'Acme Roofing']);
    Lead::factory()->create(['title' => 'Beta Siding']);

    $results = Lead::query()->withSearch('Acme')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Acme Roofing');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=LeadModelTest`
Expected: FAIL — status is a string (not enum), `withSearch` scope undefined.

- [ ] **Step 3: Update the Lead model**

Replace the contents of `app/Models/Lead.php` with:

```php
<?php

namespace App\Models;

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Models\Concerns\WithActivity;
use App\Models\Concerns\WithNotes;
use App\Models\Concerns\WithSearch;
use App\Models\Concerns\WithSort;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'type',
    'status',
    'assigned_to',
    'initiated_at',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes, WithActivity, WithNotes, WithSearch, WithSort;

    /**
     * Keep `assigned_at` in step with `assigned_to`: stamp it when a lead is
     * assigned, clear it when the assignment is removed.
     */
    protected static function booted(): void
    {
        static::saving(function (Lead $lead): void {
            if ($lead->isDirty('assigned_to')) {
                $lead->assigned_at = $lead->assigned_to ? now() : null;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'type' => LeadType::class,
            'assigned_at' => 'datetime',
            'initiated_at' => 'datetime',
        ];
    }

    /**
     * Named `assignee` (not `assignedTo`) on purpose: the snake-cased relation
     * key must not collide with the `assigned_to` foreign-key column, or the
     * serialized relation object would overwrite the id the edit form needs.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeWithSearch($query, $search): void
    {
        $this->search($query, $search, [
            'title_normalized',
        ]);
    }

    public function scopeWithSort($query, ?string $sort_by, ?string $sort_direction): void
    {
        $this->sort($query, $sort_by, $sort_direction, [
            'title',
            'status',
            'type',
            'created_at',
        ], 'title');
    }
}
```

- [ ] **Step 4: Update the factory**

Replace the contents of `database/factories/LeadFactory.php` with:

```php
<?php

namespace Database\Factories;

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(LeadStatus::cases()),
            'title' => fake()->words(3, true),
            'type' => fake()->randomElement(LeadType::cases()),
            'initiated_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'assigned_to' => User::factory(),
        ];
    }
}
```

Note: the model `saving` hook sets `assigned_at` automatically; the factory no longer sets it directly.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=LeadModelTest`
Expected: PASS (5 tests).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Lead.php database/factories/LeadFactory.php tests/Feature/LeadModelTest.php
git commit -m "Wire Lead model fillables, enum casts, notes/activity, and assigned_at automation"
```

---

### Task 3: Lead HTTP layer (request, routes, controller, notes whitelist)

**Files:**
- Create: `app/Http/Requests/LeadRequest.php`
- Create: `app/Http/Controllers/LeadController.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/NoteController.php` (add `'lead' => Lead::class` to `$models`)
- Test: `tests/Feature/LeadControllerTest.php`

**Interfaces:**
- Consumes: `Lead` model + scopes (Task 2), `LeadStatus`/`LeadType` (Task 1), `Note::TYPES`, `User`.
- Produces: resource routes named `leads.index|create|store|show|edit|update|destroy`; controller passing `users`, `statuses` (`LeadStatus::options()`), `types` (`LeadType::options()`) to `Leads/Form`, and `lead`, `notes`, `note_types` to `Leads/Show`; `'lead'` registered as a notable type.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LeadControllerTest.php`:

```php
<?php

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('lists leads on the index page', function () {
    Lead::factory()->count(3)->create();

    $this->get(route('leads.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Index', false)
            ->has('leads.data', 3)
        );
});

it('renders the create form', function () {
    $this->get(route('leads.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Form', false)
            ->has('statuses', 4)
            ->has('types', 5)
            ->has('users')
        );
});

it('stores a new lead and redirects to show', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('leads.store'), [
        'title' => 'New roof lead',
        'type' => LeadType::Web->value,
        'status' => LeadStatus::New->value,
        'assigned_to' => $user->id,
        'initiated_at' => '2026-06-20',
    ]);

    $lead = Lead::firstWhere('title', 'New roof lead');

    expect($lead)->not->toBeNull()
        ->and($lead->assigned_at)->not->toBeNull();
    $response->assertRedirect(route('leads.show', $lead));
});

it('rejects an invalid status or type', function () {
    $this->post(route('leads.store'), [
        'title' => 'Bad lead',
        'type' => 'Carrier Pigeon',
        'status' => 'Pondering',
    ])->assertSessionHasErrors(['type', 'status']);
});

it('requires a title', function () {
    $this->post(route('leads.store'), [
        'type' => LeadType::Web->value,
        'status' => LeadStatus::New->value,
    ])->assertSessionHasErrors('title');
});

it('shows a lead with notes and creation attribution', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $lead = Lead::factory()->create();

    $this->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Show', false)
            ->where('lead.creation_activity.causer.full_name', $user->full_name)
            ->has('notes')
            ->has('note_types')
        );
});

it('renders the edit form for a lead', function () {
    $lead = Lead::factory()->create();

    $this->get(route('leads.edit', $lead))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Form', false)
            ->where('lead.id', $lead->id)
        );
});

it('updates a lead and redirects to show', function () {
    $lead = Lead::factory()->create(['title' => 'Old title']);

    $this->put(route('leads.update', $lead), [
        'title' => 'Updated title',
        'type' => $lead->type->value,
        'status' => LeadStatus::Qualified->value,
    ])->assertRedirect(route('leads.show', $lead));

    expect($lead->fresh()->status)->toBe(LeadStatus::Qualified);
});

it('soft deletes a lead and redirects to index', function () {
    $lead = Lead::factory()->create();

    $this->delete(route('leads.destroy', $lead))
        ->assertRedirect(route('leads.index'));

    expect($lead->fresh()->trashed())->toBeTrue();
});

it('accepts notes against a lead', function () {
    $lead = Lead::factory()->create();

    $this->post(route('notes.store'), [
        'model_type' => 'lead',
        'model_id' => $lead->id,
        'type' => 'General',
        'title' => 'First contact',
        'content' => 'Spoke with the prospect.',
    ])->assertRedirect();

    expect($lead->notes()->count())->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=LeadControllerTest`
Expected: FAIL — route `leads.index` not defined.

- [ ] **Step 3: Create the form request**

Create `app/Http/Requests/LeadRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => [
                'required',
                Rule::enum(LeadType::class),
            ],
            'status' => [
                'required',
                Rule::enum(LeadStatus::class),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'initiated_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

Create `app/Http/Controllers/LeadController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(): Response
    {
        $leads = Lead::query()
            ->with('assignee:id,first_name,last_name')
            ->withSearch(request('search'))
            ->withSort(request('sort_by'), request('sort_direction'))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Leads/Form', $this->formOptions());
    }

    public function store(LeadRequest $request): RedirectResponse
    {
        $lead = Lead::create($request->validated());

        return redirect()->route('leads.show', $lead);
    }

    public function show(Lead $lead): Response
    {
        $lead->load([
            'assignee:id,first_name,last_name',
            'creationActivity.causer',
            'updateActivity.causer',
        ]);

        return Inertia::render('Leads/Show', [
            'lead' => $lead,
            'notes' => $lead->paginatedNotes(),
            'note_types' => Note::TYPES,
        ]);
    }

    public function edit(Lead $lead): Response
    {
        $lead->load('assignee:id,first_name,last_name');

        return Inertia::render('Leads/Form', [
            'lead' => $lead,
            ...$this->formOptions(),
        ]);
    }

    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return redirect()->route('leads.show', $lead);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index');
    }

    /**
     * Shared dropdown data for the create/edit form.
     *
     * @return array{users: \Illuminate\Support\Collection<int, User>, statuses: array, types: array}
     */
    protected function formOptions(): array
    {
        return [
            'users' => User::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'statuses' => LeadStatus::options(),
            'types' => LeadType::options(),
        ];
    }
}
```

- [ ] **Step 5: Register the route**

In `routes/web.php`, add the `LeadController` import next to the other controller imports:

```php
use App\Http\Controllers\LeadController;
```

And add the resource route directly below the `customers` resource line:

```php
Route::resource('leads', LeadController::class);
```

- [ ] **Step 6: Allow notes on leads**

In `app/Http/Controllers/NoteController.php`, add the `Lead` import:

```php
use App\Models\Lead;
```

And add the lead entry to the `$models` whitelist so it reads:

```php
    protected array $models = [
        'customer' => Customer::class,
        'company' => Company::class,
        'lead' => Lead::class,
    ];
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --compact --filter=LeadControllerTest`
Expected: PASS (10 tests). (`Leads/Index`, `Leads/Form`, `Leads/Show` are asserted with the second arg `false`, so the Vue files need not exist yet.)

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/LeadRequest.php app/Http/Controllers/LeadController.php app/Http/Controllers/NoteController.php routes/web.php tests/Feature/LeadControllerTest.php
git commit -m "Add Lead resource controller, request, routes, and note support"
```

---

### Task 4: Lead Vue pages and navigation

**Files:**
- Create: `resources/js/Pages/Leads/Index.vue`
- Create: `resources/js/Pages/Leads/Form.vue`
- Create: `resources/js/Pages/Leads/Show.vue`
- Modify: `resources/js/Layouts/AppLayout.vue` (add Leads nav link)

**Interfaces:**
- Consumes: controller props from Task 3 — Index gets `leads` (paginator); Form gets `lead?`, `users`, `statuses`, `types`; Show gets `lead`, `notes`, `note_types`. Reuses `AppLayout`, `Search`, `SortDropdown`, `Pagination`, `Card*`, `Button`, `Label`, `Input`, `Select*`, `CreatedBy`, `NotesSection`, `AddNoteButton`.
- Produces: rendered pages for the `Leads/*` Inertia components and a `leads.index` nav link.

- [ ] **Step 1: Create the index page**

Create `resources/js/Pages/Leads/Index.vue`:

```vue
<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Search from '@/Components/Search.vue'
import SortDropdown from '@/Components/SortDropdown.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Card, CardHeader, CardContent } from '@/Components/UI/card/index.js'

defineProps({
  leads: Object,
})
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between my-6">
      <h1 class="text-2xl font-bold">Leads</h1>
      <Button as-child>
        <Link :href="route('leads.create')">New lead</Link>
      </Button>
    </div>
    <div class="mb-4 flex flex-col gap-4 px-4 sm:flex-row sm:items-center">
      <Search
          :route="route('leads.index')"
          placeholder="Search leads..."
      />
      <SortDropdown
          :route="route('leads.index')"
          :options="[
              { label: 'Title', value: 'title' },
              { label: 'Status', value: 'status' },
              { label: 'Type', value: 'type' },
              { label: 'Created', value: 'created_at' },
          ]"
      />
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Card
          v-for="lead in leads.data"
          :key="lead.id"
      >
        <CardHeader class="border-b border-b-primary [.border-b]:mx-4">
          <div class="flex items-center justify-between">
            <Link
                :href="route('leads.show', lead.id)"
                class="font-bold hover:text-primary"
            >
              {{ lead.title }}
            </Link>
            <span class="text-sm text-muted-foreground">{{ lead.status }}</span>
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-sm text-muted-foreground">{{ lead.type }}</p>
          <p class="text-sm">{{ lead.assignee?.full_name || 'Unassigned' }}</p>
        </CardContent>
      </Card>
    </div>
    <Pagination :paginator="leads" />
  </AppLayout>
</template>
```

Note: the `assignee` relation serializes to the `assignee` JSON key in Inertia props; `assigned_to` stays the FK id.

- [ ] **Step 2: Create the form page (create + edit)**

Create `resources/js/Pages/Leads/Form.vue`:

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Input from '@/Components/UI/input/Input.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Label } from '@/Components/UI/label/index.js'
import { Card } from '@/Components/UI/card/index.js'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/UI/select/index.js'

const props = defineProps({
  lead: { type: Object, default: null },
  users: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
})

const form = useForm({
  title: props.lead?.title || '',
  type: props.lead?.type || '',
  status: props.lead?.status || '',
  assigned_to: props.lead?.assigned_to || null,
  initiated_at: props.lead?.initiated_at?.slice(0, 10) || '',
})

const submit = () => {
  if (props.lead) {
    form.put(route('leads.update', props.lead.id))
  } else {
    form.post(route('leads.store'))
  }
}
</script>

<template>
  <AppLayout>
    <h1 class="text-2xl font-bold my-6">{{ lead ? 'Edit lead' : 'New lead' }}</h1>
    <Card class="px-6">
      <form @submit.prevent="submit">
        <div class="w-full space-y-6 p-6">
          <div class="space-y-2">
            <Label for="title">Title</Label>
            <Input
                id="title"
                type="text"
                placeholder="Lead title"
                v-model="form.title"
            />
            <p
                v-if="form.errors.title"
                class="text-sm text-destructive"
            >
              {{ form.errors.title }}
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="type">Type</Label>
              <Select v-model="form.type">
                <SelectTrigger
                    id="type"
                    class="w-full"
                >
                  <SelectValue placeholder="Select a type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="type in types"
                      :key="type.value"
                      :value="type.value"
                  >
                    {{ type.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p
                  v-if="form.errors.type"
                  class="text-sm text-destructive"
              >
                {{ form.errors.type }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="status">Status</Label>
              <Select v-model="form.status">
                <SelectTrigger
                    id="status"
                    class="w-full"
                >
                  <SelectValue placeholder="Select a status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="status in statuses"
                      :key="status.value"
                      :value="status.value"
                  >
                    {{ status.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p
                  v-if="form.errors.status"
                  class="text-sm text-destructive"
              >
                {{ form.errors.status }}
              </p>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="assigned_to">Assigned to</Label>
              <Select v-model="form.assigned_to">
                <SelectTrigger
                    id="assigned_to"
                    class="w-full"
                >
                  <SelectValue placeholder="Unassigned" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                      v-for="user in users"
                      :key="user.id"
                      :value="user.id"
                  >
                    {{ user.full_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p
                  v-if="form.errors.assigned_to"
                  class="text-sm text-destructive"
              >
                {{ form.errors.assigned_to }}
              </p>
            </div>

            <div class="space-y-2">
              <Label for="initiated_at">Initiated at</Label>
              <Input
                  id="initiated_at"
                  type="date"
                  v-model="form.initiated_at"
              />
              <p
                  v-if="form.errors.initiated_at"
                  class="text-sm text-destructive"
              >
                {{ form.errors.initiated_at }}
              </p>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t p-6">
          <Button
              type="submit"
              :disabled="form.processing"
          >
            Save Lead
          </Button>
        </div>
      </form>
    </Card>
  </AppLayout>
</template>
```

- [ ] **Step 3: Create the show page**

Create `resources/js/Pages/Leads/Show.vue`:

```vue
<!--suppress JSUnresolvedReference -->
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreatedBy from '@/Components/CreatedBy.vue'
import NotesSection from '@/Components/Notes/NotesSection.vue'
import AddNoteButton from '@/Components/Notes/AddNoteButton.vue'
import { Button } from '@/Components/UI/button/index.js'
import { Card } from '@/Components/UI/card/index.js'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  lead: Object,
  notes: Object,
  note_types: Array,
})

const showNoteForm = ref(false)

const destroy = () => {
  if (confirm('Delete this lead?')) {
    router.delete(route('leads.destroy', props.lead.id))
  }
}
</script>

<script>
import { router } from '@inertiajs/vue3'
</script>

<template>
  <AppLayout>
    <Head :title="lead.title" />

    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">{{ lead.title }}</h1>
        <p class="text-muted-foreground">{{ lead.status }} / {{ lead.type }}</p>
        <CreatedBy
            :show_avatar="false"
            :date="lead.created_at"
            :user="lead.creation_activity?.causer"
            :updated_date="lead.updated_at"
            :updater="lead.update_activity?.causer"
        />
      </div>
      <div class="flex items-center gap-3">
        <Button
            as-child
            variant="outline"
        >
          <Link :href="route('leads.edit', lead.id)">Edit</Link>
        </Button>
        <Button
            variant="destructive"
            @click="destroy"
        >
          Delete
        </Button>
      </div>
    </div>

    <Card class="px-6">
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <dt class="text-sm text-muted-foreground">Status</dt>
          <dd>{{ lead.status }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Type</dt>
          <dd>{{ lead.type }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Assigned to</dt>
          <dd>{{ lead.assignee?.full_name || '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Initiated at</dt>
          <dd>{{ lead.initiated_at || '—' }}</dd>
        </div>
      </dl>
    </Card>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <div class="flex items-center justify-between">
          <h2 class="heading">Notes</h2>
          <AddNoteButton
              v-if="!showNoteForm"
              @click="showNoteForm = true"
          />
        </div>
        <Card class="px-6">
          <NotesSection
              v-model:show-form="showNoteForm"
              :notable_id="lead.id"
              :notes="notes"
              :types="note_types"
              notable_type="lead"
          />
        </Card>
        <Pagination :paginator="notes" />
      </div>
    </div>
  </AppLayout>
</template>
```

Note: move the `router` import into the single `<script setup>` block if the project lints against multiple `<script>` blocks — combine as `import { Head, Link, router } from '@inertiajs/vue3'` and delete the second block. (Shown split only for clarity.)

- [ ] **Step 4: Add the navigation link**

In `resources/js/Layouts/AppLayout.vue`, add a Leads link directly after the Customers link (line ~24):

```html
        <li><a :href="route('leads.index')" class="hover:text-primary">Leads</a></li>
```

- [ ] **Step 5: Combine the Show.vue script blocks**

Edit `resources/js/Pages/Leads/Show.vue`: replace the import line with `import { Head, Link, router } from '@inertiajs/vue3'` and delete the standalone `<script>...</script>` block, leaving a single `<script setup>`.

- [ ] **Step 6: Build the frontend**

Run: `npm run build`
Expected: build completes with no errors and emits the `Leads/Index`, `Leads/Form`, `Leads/Show` chunks.

- [ ] **Step 7: Run the full Lead test suite**

Run: `php artisan test --compact --filter=Lead`
Expected: PASS (all Lead enum, model, and controller tests green).

- [ ] **Step 8: Commit**

```bash
git add resources/js/Pages/Leads/ resources/js/Layouts/AppLayout.vue
git commit -m "Add Leads index, form, and show pages with navigation"
```

---

## Self-Review Notes

- **Spec coverage:** enums (Task 1); model fillable/traits/casts/assigned_at/scopes + factory (Task 2); request, full-resource controller, routes, note whitelist (Task 3); Index/Form/Show pages + nav (Task 4); tests across Tasks 1–3 plus build/render checks in Task 4. All spec sections map to a task.
- **assigned_at** is auto-managed and excluded from fillable, matching the spec.
- **initiated_at** is an editable optional date field, matching the spec.
- **Delete** is soft delete + redirect to index, no trash UI, matching the spec.
- **Resolved during planning:** the relation is named `assignee` (not `assignedTo`) so its snake-cased JSON key does not collide with the `assigned_to` FK column. JSON exposes `assigned_to` (id, for the form) and `assignee` (user object, for display).
```
