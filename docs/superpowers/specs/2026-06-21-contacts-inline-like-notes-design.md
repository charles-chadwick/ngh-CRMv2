# Contacts displayed inline, like notes

## Goal

Replace the contacts modal + single primary-preview card with an inline,
notes-style section. Contacts on a record's Show page should look and behave
like the Notes section: a heading with an "Add" button, a card containing an
inline add/edit form and the full list of items, and server-side pagination
below the card.

## Scope

Contacts render only on two pages:

- `resources/js/Pages/Customers/Show.vue`
- `resources/js/Pages/Company/Show.vue`

`Lead` does not use the `WithContacts` concern and has no contacts UI, so it is
out of scope.

## Backend

### `app/Models/Concerns/WithContacts.php`

Add a `paginatedContacts()` method mirroring `WithNotes::paginatedNotes()`:

```php
public function paginatedContacts(int $perPage = 2): LengthAwarePaginator
{
    return $this->contacts()
        ->paginate($perPage, ['*'], 'contacts_page')
        ->withQueryString();
}
```

- `$perPage` defaults to `2`, matching `paginatedNotes()`.
- Page query parameter is `contacts_page`, keeping it independent from the
  notes paginator (`notes_page`).
- The existing `contacts()` relation already orders
  `orderByDesc('is_primary')->latest()`, so the primary contact stays on top
  for free — no extra ordering needed.
- Select all columns (`['*']`) so the inline edit form receives every editable
  field.

### Controllers

In `CustomerController@show` and `CompanyController@show`:

- Pass `'contacts' => $model->paginatedContacts()` instead of the raw
  collection.
- Remove `'contacts'` from the `load([...])` eager-load call; `paginatedContacts`
  issues its own query.

## Frontend

### `ContactsSection.vue` (rewrite)

Becomes the inner component analogous to `NotesSection.vue`:

- Props: `contactable_type`, `contactable_id`, `contacts` (paginator Object with
  a `.data` array), `types`.
- `showForm` exposed via `defineModel('showForm')`; `editingId` local ref.
- Renders, in order:
  1. Inline `ContactForm` (add mode) when `showForm` is true.
  2. The list: for each `contact` in `contacts.data`, an inline `ContactForm`
     when `editingId === contact.id`, otherwise a `ContactItem`.
  3. Empty state `"No contacts yet."` when `contacts.data` is empty.
- Handlers: `onSaved`, `startEdit`, `removeContact` (router delete),
  `setPrimary` (router patch) — same as the current modal.

### Show pages (`Customers/Show.vue`, `Company/Show.vue`)

Lay the contacts column out identically to the notes column:

- Header row: `<h2 class="heading">Contacts</h2>` + an "Add contact" button
  (plain `Button` mirroring `AddNoteButton`, shown when the form is hidden,
  toggling `showContactForm`).
- `Card` wrapping `ContactsSection` (bound with `v-model:show-form`).
- `<Pagination :paginator="contacts" />` below the card.

### Removals

- Delete `resources/js/Components/Contacts/ContactsModal.vue` (and with it the
  3-per-page client-side control).
- `ContactCard.vue` and `ContactItem.vue` are unchanged — `ContactItem` already
  renders the display card plus edit/remove/set-primary actions, matching the
  notes-list shape.

## Testing

- Update the Customers and Company show feature tests to assert the `contacts`
  prop is a paginator (has `data` / `last_page`) and that the inline list
  renders.
- Confirm no remaining test references `ContactsModal`.
- Run the affected tests with `php artisan test --compact`.

## Out of scope

- Leads (no contacts).
- Changing `ContactCard` / `ContactItem` visuals.
- Any change to contact create/update/delete endpoints.
