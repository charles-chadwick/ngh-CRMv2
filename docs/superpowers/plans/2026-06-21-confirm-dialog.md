# Confirmation Dialog Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace `window.confirm()` for delete actions with a reusable, nice-looking confirmation modal wired to every delete button, usable for any yes/no confirmation.

**Architecture:** An imperative promise-based API. A module-level reactive singleton (`useConfirm`) holds dialog state and exposes a `confirm(options) => Promise<boolean>`. A single `ConfirmDialog.vue`, built on reka-ui `AlertDialog` primitives and mounted once in `AppLayout.vue`, renders that state. Call sites do `if (await confirm({...})) { ... }`.

**Tech Stack:** Vue 3 (`<script setup>`), reka-ui 2.x (`AlertDialog*`), Inertia v3, Tailwind v4, existing `UI/button` + `cn` util.

## Global Constraints

- JS naming: variables `snake_case`, functions `camelCase`, classes `TitleCase` (per global standards).
- Vue components must have a single root element (Inertia/Vue rule). `AppLayout.vue` is a layout with multiple root nodes — that is existing and allowed; `ConfirmDialog.vue` itself must have one root (the `AlertDialogRoot`).
- No new npm dependencies — reka-ui is already installed (v2.9.10).
- No server-side / Inertia / route changes.
- Confirm button color for deletes: Button `variant="destructive"`.
- Styling must mirror existing `UI/dialog/DialogContent.vue` (overlay `bg-black/50`, centered card, `animate-in`/`animate-out`, rounded border, shadow).
- No JS test harness exists; verification is `npm run build` + manual/Playwright check (see Task 6).

---

### Task 1: `useConfirm` composable

**Files:**
- Create: `resources/js/composables/useConfirm.js`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `useConfirm()` → returns `confirm(options) => Promise<boolean>`.
  - `confirm_state` — a `reactive({ open, options })` singleton (read by the dialog).
  - `resolveConfirm(result: boolean)` — called by the dialog to settle the pending promise and close.
  - `options` shape: `{ title: string, message?: string, confirmLabel?: string, cancelLabel?: string, variant?: 'default' | 'destructive' }`.
  - Defaults applied by `confirm()`: `confirmLabel='Confirm'`, `cancelLabel='Cancel'`, `variant='default'`.

- [ ] **Step 1: Write the composable**

Create `resources/js/composables/useConfirm.js`:

```js
import { reactive } from "vue";

const default_options = {
  title: "Are you sure?",
  message: "",
  confirmLabel: "Confirm",
  cancelLabel: "Cancel",
  variant: "default",
};

// Module-level singleton: one dialog instance for the whole app.
const confirm_state = reactive({
  open: false,
  options: { ...default_options },
});

let resolve_fn = null;

/**
 * Settle the pending confirm() promise and close the dialog.
 *
 * @param {boolean} result
 */
function resolveConfirm(result) {
  confirm_state.open = false;
  if (resolve_fn) {
    resolve_fn(result);
    resolve_fn = null;
  }
}

/**
 * @typedef {object} ConfirmOptions
 * @property {string} title
 * @property {string} [message]
 * @property {string} [confirmLabel]
 * @property {string} [cancelLabel]
 * @property {'default'|'destructive'} [variant]
 */

/**
 * Open the global confirmation dialog.
 *
 * @param {ConfirmOptions} options
 * @returns {Promise<boolean>} resolves true on confirm, false on cancel/dismiss
 */
function confirm(options) {
  confirm_state.options = { ...default_options, ...options };
  confirm_state.open = true;

  return new Promise((resolve) => {
    resolve_fn = resolve;
  });
}

export function useConfirm() {
  return confirm;
}

export { confirm_state, resolveConfirm };
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds (no import/syntax errors). The composable is not yet consumed, so nothing else changes.

- [ ] **Step 3: Commit**

```bash
git add resources/js/composables/useConfirm.js
git commit -m "feat: add useConfirm composable"
```

---

### Task 2: `ConfirmDialog.vue` component

**Files:**
- Create: `resources/js/Components/UI/ConfirmDialog.vue`

**Interfaces:**
- Consumes from Task 1: `confirm_state` (`{ open, options }`), `resolveConfirm(boolean)`.
- Consumes existing: `Button` from `@/Components/UI/button/index.js`, reka-ui `AlertDialog*`.
- Produces: a default-exported component that renders the global confirmation dialog. No props.

- [ ] **Step 1: Write the component**

Create `resources/js/Components/UI/ConfirmDialog.vue`:

```vue
<script setup>
import {
  AlertDialogRoot,
  AlertDialogPortal,
  AlertDialogOverlay,
  AlertDialogContent,
  AlertDialogTitle,
  AlertDialogDescription,
} from "reka-ui";
import { Button } from "@/Components/UI/button/index.js";
import { confirm_state, resolveConfirm } from "@/composables/useConfirm.js";

// We drive resolution exclusively through our own handlers so the result is
// unambiguous. The Confirm/Cancel buttons are plain Buttons (NOT
// AlertDialogAction/Cancel) — those auto-close and emit update:open, which
// could race our explicit resolve and settle the wrong value. Clicking our
// buttons calls resolveConfirm(), which flips the controlled `open` prop;
// changing a controlled prop externally does not echo update:open.
//
// reka-ui still emits update:open(false) for genuine dismissals (ESC / overlay
// click); we treat those as cancel. resolveConfirm is idempotent (guarded by
// the pending resolve fn), so a redundant call is a harmless no-op.
const onUpdateOpen = (value) => {
  if (!value) {
    resolveConfirm(false);
  }
};
</script>

<template>
  <AlertDialogRoot :open="confirm_state.open" @update:open="onUpdateOpen">
    <AlertDialogPortal>
      <AlertDialogOverlay
        class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
      />
      <AlertDialogContent
        class="fixed left-1/2 top-1/2 z-50 grid w-full max-w-md -translate-x-1/2 -translate-y-1/2 gap-4 rounded-lg border bg-background p-6 shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95"
      >
        <div class="flex flex-col gap-2">
          <AlertDialogTitle class="text-lg font-bold">
            {{ confirm_state.options.title }}
          </AlertDialogTitle>
          <AlertDialogDescription
            v-if="confirm_state.options.message"
            class="text-sm text-muted-foreground"
          >
            {{ confirm_state.options.message }}
          </AlertDialogDescription>
        </div>

        <div class="flex justify-end gap-2">
          <Button variant="outline" @click="resolveConfirm(false)">
            {{ confirm_state.options.cancelLabel }}
          </Button>
          <Button
            :variant="confirm_state.options.variant"
            @click="resolveConfirm(true)"
          >
            {{ confirm_state.options.confirmLabel }}
          </Button>
        </div>
      </AlertDialogContent>
    </AlertDialogPortal>
  </AlertDialogRoot>
</template>
```

- [ ] **Step 2: Verify it compiles**

Run: `npm run build`
Expected: build succeeds. Component exists but is not yet mounted.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/UI/ConfirmDialog.vue
git commit -m "feat: add ConfirmDialog component"
```

---

### Task 3: Mount the dialog globally

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`

**Interfaces:**
- Consumes from Task 2: `ConfirmDialog` (default export).
- Produces: the dialog is now live on every page that uses `AppLayout`.

- [ ] **Step 1: Import the component**

In `resources/js/Layouts/AppLayout.vue`, add to the `<script setup>` block (after the existing imports):

```js
import ConfirmDialog from "@/Components/UI/ConfirmDialog.vue";
```

- [ ] **Step 2: Render it in the template**

In `resources/js/Layouts/AppLayout.vue`, add `<ConfirmDialog />` as the last node inside `<template>`, immediately after the closing `</main>` tag:

```vue
  <main class="mx-auto px-32 mb-6">
    <slot />
  </main>
  <ConfirmDialog />
</template>
```

- [ ] **Step 3: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/AppLayout.vue
git commit -m "feat: mount ConfirmDialog globally in AppLayout"
```

---

### Task 4: Migrate `window.confirm()` call sites

**Files:**
- Modify: `resources/js/Pages/Leads/Show.vue:23-27`
- Modify: `resources/js/Pages/Settings/CustomerRoles/Index.vue:13-19`

**Interfaces:**
- Consumes from Task 1: `useConfirm`.

- [ ] **Step 1: Update `Leads/Show.vue`**

In `resources/js/Pages/Leads/Show.vue`, add the import to `<script setup>` (alongside the other `@/` imports):

```js
import { useConfirm } from '@/composables/useConfirm.js'
```

Then replace the existing `destroy` function:

```js
const destroy = () => {
  if (confirm('Delete this lead?')) {
    router.delete(route('leads.destroy', props.lead.id))
  }
}
```

with:

```js
const confirm = useConfirm()

const destroy = async () => {
  if (await confirm({
    title: 'Delete this lead?',
    message: 'This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'destructive',
  })) {
    router.delete(route('leads.destroy', props.lead.id))
  }
}
```

- [ ] **Step 2: Update `Settings/CustomerRoles/Index.vue`**

In `resources/js/Pages/Settings/CustomerRoles/Index.vue`, add the import to `<script setup>`:

```js
import { useConfirm } from '@/composables/useConfirm.js'
```

Then replace the existing `destroy` function:

```js
const destroy = (role) => {
  if (confirm(`Delete the "${role.name}" role?`)) {
    router.delete(route('settings.customer-roles.destroy', role.id), {
      preserveScroll: true,
    })
  }
}
```

with:

```js
const confirm = useConfirm()

const destroy = async (role) => {
  if (await confirm({
    title: `Delete the "${role.name}" role?`,
    message: 'This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'destructive',
  })) {
    router.delete(route('settings.customer-roles.destroy', role.id), {
      preserveScroll: true,
    })
  }
}
```

- [ ] **Step 3: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Leads/Show.vue resources/js/Pages/Settings/CustomerRoles/Index.vue
git commit -m "feat: use ConfirmDialog for lead and role deletes"
```

---

### Task 5: Add confirmation to section deletes

**Files:**
- Modify: `resources/js/Components/Notes/NotesSection.vue:27-29`
- Modify: `resources/js/Components/Contacts/ContactsSection.vue` (the `removeContact` function, ~line 27)

These currently delete with **no** confirmation. Add it.

**Interfaces:**
- Consumes from Task 1: `useConfirm`.

- [ ] **Step 1: Update `NotesSection.vue`**

In `resources/js/Components/Notes/NotesSection.vue`, add the import to `<script setup>`:

```js
import { useConfirm } from '@/composables/useConfirm.js'
```

Then replace the existing `removeNote` function:

```js
const removeNote = (note) => {
  router.delete(route('notes.destroy', note.id), { preserveScroll: true })
}
```

with:

```js
const confirm = useConfirm()

const removeNote = async (note) => {
  if (await confirm({
    title: 'Delete this note?',
    message: 'This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'destructive',
  })) {
    router.delete(route('notes.destroy', note.id), { preserveScroll: true })
  }
}
```

- [ ] **Step 2: Update `ContactsSection.vue`**

In `resources/js/Components/Contacts/ContactsSection.vue`, add the import to `<script setup>`:

```js
import { useConfirm } from "@/composables/useConfirm.js";
```

Then replace the existing `removeContact` function:

```js
const removeContact = (contact) => {
  router.delete(route("contacts.destroy", contact.id), { preserveScroll: true });
};
```

with:

```js
const confirm = useConfirm();

const removeContact = async (contact) => {
  if (await confirm({
    title: "Delete this contact?",
    message: "This action cannot be undone.",
    confirmLabel: "Delete",
    variant: "destructive",
  })) {
    router.delete(route("contacts.destroy", contact.id), { preserveScroll: true });
  }
};
```

- [ ] **Step 3: Verify it compiles**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Notes/NotesSection.vue resources/js/Components/Contacts/ContactsSection.vue
git commit -m "feat: confirm before deleting notes and contacts"
```

---

### Task 6: Manual end-to-end verification

**Files:** none (verification only).

No automated JS test harness exists in this repo, so verify the full flow manually (or with Playwright MCP if available).

- [ ] **Step 1: Build**

Run: `npm run build`
Expected: succeeds with no errors.

- [ ] **Step 2: Verify a delete flow**

With the app running (`composer run dev` or `npm run dev`):
1. Go to a Lead show page, click the delete (Trash) button.
2. Expected: the styled modal appears with title "Delete this lead?", a destructive (red) "Delete" button, and an outline "Cancel" button. No browser `confirm()` popup.
3. Click **Cancel** (and press **ESC**, and click the **overlay**) — modal closes, lead NOT deleted each time.
4. Reopen, click **Delete** — modal closes, lead is deleted (redirect/list updates).

- [ ] **Step 3: Spot-check the other sites**

Repeat the open → cancel → confirm check on: Customer Roles list delete, a Note delete, and a Contact delete. Each shows the modal and only deletes on confirm.

- [ ] **Step 4: Confirm no stray `window.confirm` remains**

Run: `grep -rn "confirm(" resources/js | grep -v "useConfirm\|password_confirmation\|confirm_state\|resolveConfirm\|ConfirmOptions"`
Expected: no remaining `window.confirm(...)` delete prompts (only the composable/`await confirm({...})` usages, which contain `confirm({` not `confirm('`).

- [ ] **Step 5: Final commit (if any docs/notes changed)**

No code change expected here. If the grep surfaced a missed site, fix it the same way as Task 4/5, then commit.
