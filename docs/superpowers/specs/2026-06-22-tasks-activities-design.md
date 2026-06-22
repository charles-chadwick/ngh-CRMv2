# Tasks & Activities — Design Spec

**Date:** 2026-06-22
**Status:** Approved
**Branch:** `feature/tasks-activities`

## Summary

Add a unified **Task** system to the CRM. A Task is a single concept that can be either
a dateless to-do or a timed/scheduled event, with completion state and in-app reminders.
Tasks attach polymorphically to a Company, Customer, or Lead (or stand alone), and are
assigned polymorphically to a User or a Customer.

The model is named **`Task`** (not "Activity") deliberately: the codebase already uses
"activity" for the Spatie activity log (`WithActivity`, `creationActivity`,
`updateActivity`). Reusing that word would be confusing.

Tasks are surfaced three ways: a per-record list section (alongside Notes), a "My
activities" page with List ↔ Calendar toggle, and a dashboard widget. The calendar uses
**FullCalendar** (`@fullcalendar/vue3` + core/daygrid/timegrid/interaction) with
drag-to-reschedule.

### Relationship to Notes (decided)

Tasks and Notes stay **separate but linkable**. A Note records what happened; a Task is
something to do/scheduled. Note types (`Call`, `Meeting`, `Email`, `Follow Up`) are left
as-is. When a Task is completed, the user may optionally drop a Note on the same subject
(the "log a note" bridge).

## Data Model

`tasks` table:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint pk | |
| `type` | string (enum `TaskType`) | `Call`, `Meeting`, `Email`, `Follow Up`, `To-Do` |
| `title` | string | required |
| `description` | longText nullable | free-form detail |
| `status` | string (enum `TaskStatus`) | `Open`, `Completed`, `Canceled` |
| `due_at` | datetime nullable | due time / event start |
| `all_day` | boolean | true = dateless to-do / all-day item |
| `remind_at` | datetime nullable | when the in-app reminder fires |
| `reminded_at` | datetime nullable | set when reminder sent; prevents double-fire |
| `completed_at` | datetime nullable | |
| `subject_type` / `subject_id` | nullable morph | Company / Customer / Lead (nullable = personal task) |
| `assignee_type` / `assignee_id` | nullable morph | User or Customer |
| `timestamps`, `softDeletes` | | |

Creator/updater come from the existing `WithActivity` (Spatie) trait, same as Notes — no
extra columns.

**Conventions:** enums in `app/Enum` (`TaskType`, `TaskStatus`); polymorphic morphs
mirror `Note.notable` / `Contact.contactable`; soft deletes; times stored UTC and
rendered in app timezone; all-day items pinned to a date.

**`remind_at` default:** on save, if blank, default to `due_at` (all-day → 9:00am that
date). User can override or clear it; cleared = no reminder.

## Backend

**Model `app/Models/Task.php`**
- Relations: `subject()` morphTo, `assignee()` morphTo
- Casts: datetimes for `due_at`/`remind_at`/`reminded_at`/`completed_at`; `type`/`status`
  to enums; `all_day` to bool
- Scopes: `open()`, `completed()`, `overdue()` (open + `due_at < now`), `dueOn($date)`,
  `forAssignee($model)`, `between($start, $end)` (calendar range)
- Helpers: `markCompleted()`, `markCanceled()`, `reopen()`

**Concern `app/Models/Concerns/WithTasks.php`** (mirrors `WithNotes`)
- `tasks()` morphMany on `subject`, ordered by `due_at`
- `paginatedTasks()` for record-page list section
- Added to `Company`, `Customer`, `Lead`

**Requests**
- `TaskRequest` — store/update: type, title, description, due_at, all_day, remind_at,
  subject, assignee
- `TaskStatusRequest` — status transition + optional `log_note`

**Controller `TaskController`** (mirrors `NoteController` + `ContactController`)
- `index` — "My activities" page: list groups (Overdue / Due Today / Upcoming /
  Completed) **and** a calendar feed for the visible range
- `store` / `update` / `destroy`
- `updateStatus` (`PATCH /tasks/{task}/status`) — complete / cancel / reopen; optional
  `log_note` on completion creates a `Note` on the same subject
- `assignees` (`GET /tasks/assignees`) — search Users + Customers for the picker, reusing
  the `TopicController@participants` search pattern

**Routes**
```
GET    /tasks                 tasks.index
GET    /tasks/assignees       tasks.assignees
POST   /tasks                 tasks.store
PUT    /tasks/{task}          tasks.update
PATCH  /tasks/{task}/status   tasks.updateStatus
DELETE /tasks/{task}          tasks.destroy
```

**Reminders**
- `TaskReminder` database notification (mirrors `UserMentionedInNote`), delivered to the
  assignee
- `SendTaskReminders` console command, scheduled every 5 min: finds `Open` tasks where
  `remind_at <= now` and `reminded_at is null` and an assignee exists → notifies → stamps
  `reminded_at`
- Customer assignees: notification is stored but won't surface until a customer portal
  exists — designed-for, not blocked-on

**Factory + Seeder** — `TaskFactory` (states: `overdue`, `dueToday`, `completed`,
`allDay`), `TaskSeeder` referencing only existing users/customers/companies/leads per the
project seeding convention.

## Frontend (Inertia/Vue + FullCalendar)

**Pages**
- `Pages/Tasks/Index.vue` — "My activities". List ↔ Calendar toggle.
  - *List mode:* grouped sections (Overdue, Due Today, Upcoming, collapsible Completed)
    with mine / all / by-assignee filter.
  - *Calendar mode:* FullCalendar month/week/day. All-day tasks in the all-day row, timed
    events in slots. Drag-to-reschedule → `PUT /tasks/{task}` (optimistic, rollback on
    error). Click a day/slot → create modal pre-filled with that date.

**Components `Components/Tasks/`**
- `TaskForm.vue` — create/edit modal (type, title, description, subject picker, assignee
  picker, due date/time + all-day toggle, remind_at); uses `useForm`
- `TaskItem.vue` — one row: type icon, title, due/overdue badge, assignee avatar, status
  toggle
- `TaskStatusToggle.vue` — complete / reopen / cancel; on complete offers optional "log a
  note" field
- `TasksSection.vue` — embeddable paginated list for record pages (mirrors Notes), with
  "Add task" button
- `AssigneePicker.vue` / `SubjectPicker.vue` — search-as-you-type (reuse
  `ParticipantPicker.vue` pattern)
- `TaskCalendar.vue` — wraps FullCalendar; maps tasks → events; emits reschedule/select

**Embeds**
- `TasksSection` added to `Company/Show.vue`, `Customers/Show.vue`, `Leads/Show.vue`
  alongside Notes
- `Home.vue` dashboard — "My activities" widget (Overdue + Due Today)
- Nav — a **"Tasks"** link to `tasks.index`

**Calendar data flow:** calendar fetches its visible range via `index` (range params), so
all tasks are never loaded at once. Reschedule and status changes use Inertia partial
reloads to keep lists and calendar in sync. Drag-to-reschedule is included from day one.

## Testing (Pest feature tests)

- Create a task against each subject type (Company/Customer/Lead) and as standalone
- Assign to a User and to a Customer (polymorphic assignee)
- Status transitions: complete (stamps `completed_at`), cancel, reopen; completing with
  `log_note` creates a `Note` on the same subject
- Scopes: `overdue`, `dueOn(today)`, `forAssignee`, `between`
- `SendTaskReminders`: fires `TaskReminder` once for a due/open/assigned task; stamps
  `reminded_at`; does not re-fire; skips completed/canceled/unassigned
- `all_day` task applies no time component; timed task preserves time
- Validation: `TaskRequest` rejects bad enum / missing title; status request rejects
  illegal transitions
- `index` returns expected list groups + calendar feed shape

## Edge Cases

- Soft-deleted subject: task survives, UI shows "—" subject
- `remind_at` in the past on create: reminder fires on next sweep (expected)
- Completing/canceling stops future reminders (sweep filters `Open`)
- Timezone: store UTC, render in app tz; all-day pinned to date
- Customer assignee reminder stored but not surfaced (documented limitation)

## Dependencies (approved)

New frontend deps: `@fullcalendar/vue3`, `@fullcalendar/core`, `@fullcalendar/daygrid`,
`@fullcalendar/timegrid`, `@fullcalendar/interaction`.
