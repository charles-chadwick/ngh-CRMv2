# Contacts Inline (Like Notes) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the contacts modal + primary-preview card with an inline, notes-style contacts section that has server-side pagination.

**Architecture:** Mirror the existing Notes pattern. A `paginatedContacts()` model method (in the `WithContacts` concern) feeds a paginator to the Customers/Company Show pages. `ContactsSection.vue` is rewritten to be the inner inline form+list component (analogous to `NotesSection.vue`); the Show pages wrap it with a heading, an Add button, and the shared `<Pagination>` component. The modal is deleted.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Pest v4. Existing `contacts()` relation already orders `is_primary DESC, latest()`.

## Global Constraints

- Naming: `snake_case` variables, `camelCase` methods, `TitleCase` classes.
- PHP: curly braces on all control structures; explicit return types and param type hints; PHPDoc over inline comments.
- Seeders/factories: reference existing users only — never create users to satisfy a relationship.
- Contacts paginator default: **2 per page**, page param `contacts_page` (matches notes' `notes_page` / perPage 2).
- Run `vendor/bin/pint --dirty --format agent` after PHP changes.
- Tests: `php artisan test --compact --filter=<name>`.

---

### Task 1: Backend — `paginatedContacts()` + controllers

**Files:**
- Modify: `app/Models/Concerns/WithContacts.php`
- Modify: `app/Http/Controllers/CustomerController.php:55-73` (show)
- Modify: `app/Http/Controllers/CompanyController.php:28-46` (show)
- Test: `tests/Feature/ContactShowPropsTest.php`

**Interfaces:**
- Produces: `WithContacts::paginatedContacts(int $perPage = 2): LengthAwarePaginator` — returns contacts as a paginator using page name `contacts_page`, primary-first (inherited from the `contacts()` relation ordering). Show pages now pass `'contacts'` as this paginator (shape `{ data: [...], last_page, ... }`).

- [ ] **Step 1: Update the failing test**

Edit `tests/Feature/ContactShowPropsTest.php` so both cases assert the paginator shape:

```php
it('passes contacts and contact types to the customer show page', function () {
    $customer = Customer::factory()->create();
    Contact::factory()->forContactable($customer)->primary()->create();

    $this->get(route('customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customers/Show', false)
            ->has('contacts.data', 1)
            ->where('contacts.data.0.is_primary', true)
            ->has('contacts.last_page')
            ->has('contact_types')
        );
});

it('passes contacts and contact types to the company show page', function () {
    CompanyType::create(['name' => 'Airplane Parts']);
    $company = Company::factory()->create();
    Contact::factory()->forContactable($company)->create();

    $this->get(route('companies.show', $company))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Company/Show', false)
            ->has('contacts.data', 1)
            ->has('contacts.last_page')
            ->has('contact_types')
        );
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=ContactShowPropsTest`
Expected: FAIL — `contacts` is currently a bare array, so `contacts.data` / `contacts.last_page` are missing.

- [ ] **Step 3: Add `paginatedContacts()` to the concern**

In `app/Models/Concerns/WithContacts.php`, add the `LengthAwarePaginator` import and the method (mirrors `WithNotes::paginatedNotes()`):

```php
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
```

```php
/**
 * Paginate this record's contacts (primary first) for the show page.
 */
public function paginatedContacts(int $perPage = 2): LengthAwarePaginator
{
    return $this->contacts()
        ->paginate($perPage, ['*'], 'contacts_page')
        ->withQueryString();
}
```

- [ ] **Step 4: Update the controllers**

In `CustomerController@show`, remove `'contacts'` from the `$customer->load([...])` array and change the render prop:

```php
'contacts' => $customer->paginatedContacts(),
```

In `CompanyController@show`, remove `'contacts'` from the `$company->load([...])` array and change the render prop:

```php
'contacts' => $company->paginatedContacts(),
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=ContactShowPropsTest`
Expected: PASS (2 passed).

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Concerns/WithContacts.php app/Http/Controllers/CustomerController.php app/Http/Controllers/CompanyController.php tests/Feature/ContactShowPropsTest.php
git commit -m "feat: paginate contacts for show pages via paginatedContacts()"
```

---

### Task 2: Frontend — inline section, Show pages, delete modal

**Files:**
- Modify: `resources/js/Components/Contacts/ContactsSection.vue` (full rewrite)
- Modify: `resources/js/Pages/Customers/Show.vue:109-114`
- Modify: `resources/js/Pages/Company/Show.vue` (contacts block)
- Delete: `resources/js/Components/Contacts/ContactsModal.vue`
- Test: `tests/Feature/ContactControllerTest.php` (regression check only)

**Interfaces:**
- Consumes: `contacts` paginator from Task 1 (uses `contacts.data`).
- `ContactsSection` props: `contactable_type: String`, `contactable_id: [Number,String]`, `contacts: Object`, `types: Array`; model `show-form: Boolean`.

- [ ] **Step 1: Rewrite `ContactsSection.vue`**

Replace the entire file with the notes-style inner component:

```vue
<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import ContactItem from "@/Components/Contacts/ContactItem.vue";
import ContactForm from "@/Components/Contacts/ContactForm.vue";

const props = defineProps({
  contactable_type: { type: String, required: true },
  contactable_id: { type: [Number, String], required: true },
  contacts: { type: Object, required: true },
  types: { type: Array, default: () => [] },
});

const showForm = defineModel("show-form", { type: Boolean, default: false });
const editingId = ref(null);

const onSaved = () => {
  showForm.value = false;
  editingId.value = null;
};

const startEdit = (contact) => {
  showForm.value = false;
  editingId.value = contact.id;
};

const removeContact = (contact) => {
  router.delete(route("contacts.destroy", contact.id), { preserveScroll: true });
};

const setPrimary = (contact) => {
  router.patch(
    route("contacts.set-primary", contact.id),
    {},
    { preserveScroll: true },
  );
};
</script>

<template>
  <section>
    <ContactForm
        v-if="showForm"
        :contactable_type="contactable_type"
        :contactable_id="contactable_id"
        :types="types"
        class="mb-4"
        @saved="onSaved"
        @cancel="showForm = false"
    />

    <div
        v-if="contacts.data.length"
        class="space-y-3"
    >
      <template
          v-for="contact in contacts.data"
          :key="contact.id"
      >
        <ContactForm
            v-if="editingId === contact.id"
            :contactable_type="contactable_type"
            :contactable_id="contactable_id"
            :contact="contact"
            :types="types"
            @saved="onSaved"
            @cancel="editingId = null"
        />
        <ContactItem
            v-else
            :contact="contact"
            @edit="startEdit"
            @remove="removeContact"
            @set-primary="setPrimary"
        />
      </template>
    </div>
    <p
        v-else
        class="text-sm text-muted-foreground"
    >
      No contacts yet.
    </p>
  </section>
</template>
```

- [ ] **Step 2: Update `Customers/Show.vue`**

Add a `showContactForm` ref next to `showNoteForm`:

```js
const showNoteForm = ref(false)
const showContactForm = ref(false)
```

Replace the `<ContactsSection ... />` block (lines ~109-114) with the notes-style layout. Keep the `Pencil`/`Plus` imports — add `Plus` from `lucide-vue-next` and `Pagination` is already imported:

```vue
      <div>
        <div class="flex items-center justify-between">
          <h2 class="heading">Contacts</h2>
          <Button
              v-if="!showContactForm"
              variant="default"
              size="icon-sm"
              aria-label="Add Contact"
              title="Add Contact"
              @click="showContactForm = true"
          >
            <Plus />
          </Button>
        </div>
        <Card class="px-6">
          <ContactsSection
              v-model:show-form="showContactForm"
              :contactable_id="customer.id"
              :contacts="contacts"
              :types="contact_types"
              contactable_type="customer"
          />
        </Card>
        <Pagination :paginator="contacts" />
      </div>
```

Update the `Pencil` import line to also import `Plus`:

```js
import { Pencil, Plus } from "lucide-vue-next";
```

- [ ] **Step 3: Update `Company/Show.vue`**

Apply the same change as Step 2 to the contacts block in `Company/Show.vue`: add a `showContactForm` ref, ensure `Button`, `Card`, `Pagination`, and `Plus` (from `lucide-vue-next`) are imported, replace the `<ContactsSection />` usage with the heading + Add button + `Card` wrapper + `<Pagination :paginator="contacts" />`, binding `contactable_type="company"` and `:contactable_id="company.id"`.

- [ ] **Step 4: Delete the modal**

```bash
git rm resources/js/Components/Contacts/ContactsModal.vue
```

- [ ] **Step 5: Verify no references to the modal remain**

Run: `grep -rn "ContactsModal" resources/ tests/`
Expected: no output.

- [ ] **Step 6: Build the frontend**

Run: `npm run build`
Expected: builds with no errors and no unresolved imports.

- [ ] **Step 7: Regression — contact mutation tests still pass**

Run: `php artisan test --compact --filter=ContactControllerTest`
Expected: PASS (create/update/delete/set-primary endpoints unchanged).

- [ ] **Step 8: Commit**

```bash
git add resources/js/Components/Contacts/ContactsSection.vue resources/js/Pages/Customers/Show.vue resources/js/Pages/Company/Show.vue
git commit -m "feat: display contacts inline like notes; remove contacts modal"
```

---

### Task 3: Full suite verification

**Files:** none (verification only).

- [ ] **Step 1: Run the contact + show test suites**

Run: `php artisan test --compact --filter="Contact|CompanyShow|CustomerShow"`
Expected: all PASS.

- [ ] **Step 2: Run the full suite once**

Run: `php artisan test --compact`
Expected: all PASS (no test still assumes a bare `contacts` array or the modal).
