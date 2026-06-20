# Leads CRUD — Design

**Date:** 2026-06-20
**Status:** Approved

## Goal

Deliver full CRUD for the `Lead` model — the first model in the app to expose
create/store/destroy — and bring its `Show` page to parity with Customer/Company
by adding Notes and created/updated-by attribution.

## Context

- `Lead` currently uses only `HasFactory, SoftDeletes` and is the odd model out:
  `Customer` and `Company` both use `WithActivity` + `WithNotes`, but `Lead` has
  neither, and there is no Lead UI at all.
- Neither `CustomerController` nor `CompanyController` implements
  `create`/`store`/`destroy` — they expose only `index`/`show`/`edit`/`update`.
  Leads will be the first model with full resource CRUD.
- Enums live in `app/Enum/` (singular), following `App\Enum\UserRole`, which
  exposes a static `options(): list<array{value,label}>` helper for dropdowns.
- The `leads` table already exists with: `assigned_to` (nullable FK),
  `assigned_at`, `initiated_at`, `status`, `title`, `type`, a generated
  `title_normalized` index column, timestamps, soft deletes.

## Decisions

- **Scope:** Full CRUD (index, create, store, show, edit, update, destroy).
- **status / type:** PHP string enums + dropdowns now. These will later become
  database relations; isolating them as enums means only the cast and the form
  options change at that point.
- **Notes / attribution:** Full parity — `WithNotes` + `WithActivity` on the
  model, Notes panel and `UserAttribution` on `Leads/Show`. No `WithAvatar`
  (Leads are not people).
- **`assigned_at`:** Auto-managed by the model, never on the form.
- **`initiated_at`:** Optional, editable date field on the form.
- **Delete:** Soft delete + redirect to index. No trash/restore UI (YAGNI).

## Components

### 1. Enums (`app/Enum/`)

Mirror `UserRole`: backed string enum with a static `options()` helper.

- `LeadStatus: string` — `New`, `Working`, `Qualified`, `Closed`
- `LeadType: string` — `Web`, `Phone`, `Email`, `Event`, `Other`

### 2. `Lead` model

- `#[Fillable(['title', 'type', 'status', 'assigned_to', 'initiated_at'])]`
  (PHP attribute style, matching `Customer`). `assigned_at` is intentionally
  excluded — it is managed automatically.
- Traits: add `WithActivity`, `WithNotes`, `WithSearch`, `WithSort` (keep
  `HasFactory`, `SoftDeletes`). No `WithAvatar`.
- Casts: `status => LeadStatus::class`, `type => LeadType::class`,
  `assigned_at => 'datetime'`, `initiated_at => 'datetime'`.
- Keep existing `assignedTo()` `BelongsTo`.
- Auto-manage `assigned_at` via a `saving` model event: when `assigned_to`
  becomes dirty and is non-null, set `assigned_at = now()`; when it is cleared,
  null `assigned_at`.
- `scopeWithSearch($query, $search)` over `title_normalized`.
- `scopeWithSort($query, $sort_by, $sort_direction)` over
  `['title', 'status', 'type', 'created_at']`, default `title`.

### 3. `LeadRequest` (form request, store + update)

- `title` — required, string, max:255
- `type` — required, `Rule::enum(LeadType::class)`
- `status` — required, `Rule::enum(LeadStatus::class)`
- `assigned_to` — nullable, integer, exists:users,id
- `initiated_at` — nullable, date
- `authorize(): true` (matches sibling requests)

### 4. `LeadController` (full resource)

- `index` — eager-loads the `assignedTo` relation (id + display-name columns),
  applies `withSearch(request('search'))` and
  `withSort(request('sort_by'), request('sort_direction'))`, then
  `paginate(15)->withQueryString()`; renders `Leads/Index`.
- `create` — renders `Leads/Form` with `users`, `statuses` (`LeadStatus::options()`),
  `types` (`LeadType::options()`).
- `store(LeadRequest)` — `Lead::create($validated)`; redirect to `leads.show`.
- `show(Lead)` — loads `assignedTo`, `creationActivity.causer`,
  `updateActivity.causer`; passes `paginatedNotes()` and `Note::TYPES`; renders
  `Leads/Show`.
- `edit(Lead)` — loads `assignedTo`; renders `Leads/Form` with `lead`, `users`,
  `statuses`, `types`.
- `update(LeadRequest, Lead)` — `$lead->update($validated)`; redirect to
  `leads.show`.
- `destroy(Lead)` — `$lead->delete()` (soft); redirect to `leads.index`.

### 5. Route

`Route::resource('leads', LeadController::class);` in the same group as
`customers`/`companies`.

### 6. Vue pages (`resources/js/Pages/Leads/`)

- **`Index.vue`** — mirrors `Customers/Index`: table (title, status, type,
  assigned-to, created), search, sortable columns, pagination, "New lead" button
  linking to `leads.create`, rows linking to `leads.show`.
- **`Form.vue`** — dual-mode (create and edit), branching on a `lead` prop:
  `form.post(route('leads.store'))` when absent, `form.put(route('leads.update',
  lead.id))` when present. Fields: title (text), type (select), status (select),
  assigned_to (select, optional), initiated_at (date, optional). This is the one
  departure from `Customers/Form` (edit-only) because full CRUD needs create.
- **`Show.vue`** — mirrors `Customers/Show`: header with title and status/type
  badges, assigned-to, `CreatedBy`/`UserAttribution` for created + updated, Notes
  panel (`AddNoteButton` + `NoteItem`), Edit and Delete actions.

### 7. `LeadFactory`

Replace `faker->word()` for `status`/`type` with random real enum cases
(`fake()->randomElement(LeadStatus::cases())` etc.). Keep existing
`assigned_to => User::factory()` and timestamps.

### 8. Navigation

Add a Leads link to `resources/js/Layouts/AppLayout.vue` alongside
Customers/Companies.

## Testing

Pest feature test (`tests/Feature/`) covering the full CRUD cycle:

- `index` / `create` / `edit` / `show` render the expected Inertia pages
- `store` creates a Lead from valid data and redirects to `show`
- `store`/`update` reject invalid `status`/`type` (enum validation) and missing
  `title`
- `show` includes paginated notes and creation/update activity
- `destroy` soft-deletes and redirects to `index`
- creation and update activity attribution is recorded (mirrors
  `ActivityLogTest`)

All tests use `LeadFactory`.

## Out of Scope

- Trash/restore UI for soft-deleted leads.
- Converting `status`/`type` to database relations (future work; the enum
  boundary is designed to make this a localized change).
- Backfilling create/store/destroy onto Customer/Company.
