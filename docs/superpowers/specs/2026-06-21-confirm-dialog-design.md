# Reusable Confirmation Dialog — Design

**Date:** 2026-06-21
**Branch:** `feature/confirm-dialog`

## Goal

Replace `window.confirm()` for delete actions with a nice-looking, reusable
confirmation modal, wired to every delete button in the app. The dialog must be
general enough to serve any yes/no confirmation ("Discard changes?", "Send
now?"), not just deletes.

## Approach

Imperative, promise-based API backed by a single globally-mounted dialog. Call
sites do:

```js
const confirm = useConfirm()

const destroy = async () => {
  if (await confirm({ title: 'Delete lead?', message: 'This cannot be undone.', confirmLabel: 'Delete', variant: 'destructive' })) {
    router.delete(route('leads.destroy', props.lead.id))
  }
}
```

This is a drop-in replacement for `window.confirm`, minimal per-site code, and
trivially reusable for non-delete purposes.

## Components

### 1. `useConfirm` composable — `resources/js/composables/useConfirm.js`

- A **module-level reactive singleton** holding dialog state: `open`, the current
  `options`, and the pending promise's `resolve` reference.
- Exports `useConfirm()` returning a `confirm(options)` function that:
  - merges `options` over defaults,
  - opens the dialog,
  - returns a `Promise<boolean>` (`true` = confirmed, `false` = cancelled/dismissed).
- Also exports the internal state + `resolveConfirm(result)` helper for the
  dialog component to consume. Because state is a module singleton, any component
  can call `confirm()` with no provide/inject or prop-drilling.

**Options shape:**

```
{
  title: string,                       // required
  message?: string,                    // optional body text
  confirmLabel?: string = 'Confirm',
  cancelLabel?: string  = 'Cancel',
  variant?: 'default' | 'destructive' = 'default',
}
```

`variant: 'destructive'` renders the confirm button in the destructive color
(used for all deletes).

### 2. `ConfirmDialog.vue` — `resources/js/Components/UI/ConfirmDialog.vue`

- Built **directly on reka-ui `AlertDialog*` primitives** (`AlertDialogRoot`,
  `AlertDialogPortal`, `AlertDialogOverlay`, `AlertDialogContent`,
  `AlertDialogTitle`, `AlertDialogDescription`, `AlertDialogCancel`,
  `AlertDialogAction`). `AlertDialog` is the semantically correct primitive for
  confirmations: `role="alertdialog"`, no dismiss-X, ESC / overlay click =
  cancel, focus trapped, focus returns to trigger on close.
- Binds `open` to the singleton state; renders `title`, optional `message`, and
  the two buttons from `options`.
- Cancel → `resolveConfirm(false)`; Action → `resolveConfirm(true)`. ESC / overlay
  dismiss also resolves `false`.
- Styling mirrors the existing `UI/dialog/DialogContent.vue` (overlay
  `bg-black/50`, centered card, `animate-in`/`animate-out`, rounded border,
  shadow) and uses the existing `UI/button` for actions so it matches the app.

### 3. Global mount — `resources/js/Layouts/AppLayout.vue`

Add `<ConfirmDialog />` as a sibling root node in the layout template so it's
present on every page exactly once.

## Call-site migration

Replace each delete path with `await confirm(...)`, `variant: 'destructive'`:

| File | Current state | Change |
|------|---------------|--------|
| `Pages/Leads/Show.vue` | `window.confirm()` | swap to `useConfirm` |
| `Pages/Settings/CustomerRoles/Index.vue` | `window.confirm()` | swap to `useConfirm` |
| `Components/Notes/NotesSection.vue` | **no confirmation** | add confirm before delete |
| `Components/Contacts/ContactsSection.vue` | **no confirmation** | add confirm before delete |
| `Form.vue` pages (`Customers`, `Users`, `Topics`, `Company`, `Leads`, `Settings/CustomerRoles`) | audit | wire any delete buttons found |

The `destroy` handlers become `async`; the rest of each handler is unchanged.

## Out of scope / non-goals

- No server-side / Inertia / route changes — purely client-side.
- No queued/stacked confirmations (single global dialog; one at a time is
  sufficient for this app).
- No promise-rejection on cancel — cancel resolves `false` (matches `confirm()`
  ergonomics).

## Testing

- No JS test harness exists in this repo; CLAUDE.md test enforcement targets
  PHP/Pest and there are no backend changes here.
- Verify with `npm run build` (compiles cleanly) and a manual / Playwright check
  of one delete flow (open → cancel → reopen → confirm → row removed).
