# Contacts — Design

**Date:** 2026-06-20
**Branch:** feature/notes-mentions-notifications (contacts work)
**Status:** Approved (design); pending implementation plan

## Summary

Add polymorphic **Contacts** (address + work/mobile phone, classified by `type`) to
**Customers** and **Companies**. On each entity's Show page, the **primary** contact
renders inline; all other contacts live behind a **modal** that supports full CRUD
(add / edit / delete / set-primary). The implementation mirrors the existing **Notes**
feature end-to-end.

A `Contact` model, migration, and factory already exist but are disconnected and contain
a typo (`contacable` instead of `contactable`). This work fixes that and integrates the
feature fully.

## Decisions

- **Scope:** Full stack — backend (model/migration/controller/routes/request/seeder) and
  frontend (Vue components + a new Dialog primitive).
- **Primary contact:** explicit `is_primary` boolean on the `contacts` table. Exactly one
  primary per parent, enforced server-side.
- **Modal capability:** full CRUD (add, edit, delete, set-primary).
- **Contact types:** `['Office', 'Billing', 'Shipping', 'Mailing', 'Other']`.
- **Controller shape:** resource-style `store`/`update`/`destroy` plus a dedicated
  `contacts.set-primary` route. Primary enforcement (unset siblings) lives in one place,
  inside a DB transaction. Mirrors `NoteController`.
- **Migration:** edit the existing, never-shipped `create_contacts_table` migration to add
  `is_primary`, rather than stacking a second migration on an unreleased table. Re-run
  `migrate:fresh`.

## Backend

### Bug fix
- `app/Models/Contact.php`: `$fillable` and the relation method use `contacable*`
  (missing `t`). Migration already uses correct `contactable*`. Fix the model:
  `contactable_id` / `contactable_type`, rename relation to `contactable()`.
- `database/factories/ContactFactory.php`: update to match corrected columns.

### Schema (edit existing `..._create_contacts_table.php`)
Add `$table->boolean('is_primary')->default(false);`. Final columns:
`contactable_id`, `contactable_type`, `type`, `is_primary`, `street_address`, `city`,
`state`, `zip`, `work_phone`, `mobile_phone`, timestamps, soft deletes.

### Model — `App\Models\Contact`
- `const TYPES = ['Office', 'Billing', 'Shipping', 'Mailing', 'Other'];`
- Cast `is_primary` to `boolean`.
- `contactable(): MorphTo` (fixed).

### Parent models — `Customer`, `Company`
- `contacts(): MorphMany` ordered `is_primary desc`.
- `primaryContact(): MorphOne` where `is_primary = true`.

### `ContactController` (mirrors `NoteController`)
- `store` — resolves parent from `contactable_type` + `contactable_id`, creates contact.
- `update` — updates a contact.
- `destroy` — soft-deletes a contact.
- `setPrimary` — marks a contact primary.
- Setting primary (via create-with-primary, `update`, or `setPrimary`) unsets sibling
  primaries within a DB transaction so only one primary exists per parent.

### `ContactRequest`
- `type` required, `in:Contact::TYPES`.
- `street_address`, `city`, `state`, `zip`, `work_phone`, `mobile_phone` — nullable strings.
- `is_primary` — boolean.
- `contactable_type`, `contactable_id` — required on `store` only.

### Routes (flat resource style, matching existing routes)
- `contacts.store` (POST)
- `contacts.update` (PATCH)
- `contacts.destroy` (DELETE)
- `contacts.set-primary` (PATCH)

### Show controllers
`CustomerController@show` and `CompanyController@show` eager-load `contacts` and pass
`contacts` (array) + `contact_types => Contact::TYPES` to the Inertia page, alongside the
existing notes props.

### Seeder
`ContactSeeder` — creates 1–3 contacts per existing Customer and Company (exactly one
primary each), wired into `DatabaseSeeder`. Only references existing records per project
seeder convention; never creates parents to satisfy the relationship.

## Frontend

### New UI primitive — `resources/js/Components/UI/dialog/`
`reka-ui` (already installed, v2) wrappers: `DialogRoot`, `DialogTrigger`, `DialogPortal`,
`DialogOverlay`, `DialogContent`, `DialogTitle`, `DialogClose`. Styled to match existing
shadcn-style `Select`/`Card`. Exposed via an `index.js` barrel.

### Feature components — `resources/js/Components/Contacts/` (mirrors Notes)
- **`ContactsSection.vue`** — entry point for a Show page. Props: `contactable_type`,
  `contactable_id`, `contacts` (array), `types`. Renders the primary contact inline via
  `ContactCard`; a "View all contacts (N)" button opens `ContactsModal`. With zero
  contacts, shows an "Add contact" button that opens the modal straight to the form.
- **`ContactCard.vue`** — pure display of one contact: type badge, formatted address block,
  work/mobile phones, "Primary" marker. No logic. Reused inline and in the modal list.
- **`ContactsModal.vue`** — the `Dialog`. Lists contacts as `ContactItem`; an "Add contact"
  button toggles an inline `ContactForm` (same `showForm` toggle pattern as `NotesSection`).
  Saving/closing refreshes the page's `contacts` prop.
- **`ContactItem.vue`** — one modal row: `ContactCard` plus Edit (swaps row for inline
  `ContactForm`, like `NotesSection`'s `editingId`), Delete, and Set primary (hidden when
  already primary).
- **`ContactForm.vue`** — add/edit form via `useForm`. Fields: `type` (Select from `types`),
  `street_address`, `city`, `state`, `zip`, `work_phone`, `mobile_phone`, `is_primary`
  checkbox. `post` to `contacts.store` / `patch` to `contacts.update`, `preserveScroll`;
  emits `saved` / `cancel`. Built from `Card`, `Label`, `Input`, `Select`, `Button`.

### Data flow
Identical to Notes: forms submit via Inertia, controller redirects back, the Show page's
`contacts` prop re-renders. Set-primary is a `patch` to `contacts.set-primary`. No
client-side store.

### Wiring
Add `<ContactsSection>` to both `Customers/Show.vue` and `Company/Show.vue`, beside the
existing Notes section, fed by the new `contacts` / `contact_types` props.

## Testing

- Pest feature tests for `ContactController`: store, update, destroy, set-primary;
  validation failures; polymorphic attachment to both Customer and Company; the
  "only one primary per parent" invariant.
- Smoke test: both Show pages render without JS errors with contacts present.

## Out of scope
- A standalone Contacts index/listing page.
- Contact-level activity log / notes.
- Attaching contacts to entities other than Customer and Company (Leads, etc.).
