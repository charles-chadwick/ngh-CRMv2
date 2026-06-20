# Rich-text notes with @mentions + real-time notifications

**Status:** Approved design — ready for implementation planning
**Date:** 2026-06-20
**Author:** Charles (with Claude)

## Summary

Add a Quill rich-text editor to the note content field, let authors `@mention`
team members inside that editor, and notify mentioned users through a persisted
in-app notification center with a live toast pushed over Laravel Reverb.

The work is delivered in three independently shippable, independently tested
phases:

1. **Rich text** — Quill + quill-mention editor, sanitized-HTML storage, `v-html` rendering.
2. **Mentions + database notifications** — mentions pivot, mention service, notification, notification-center UI (no real-time yet).
3. **Reverb real-time** — broadcasting install, Echo client, live toast.

## Decisions (locked during brainstorming)

| Decision | Choice |
| --- | --- |
| Notification surface | DB-persisted notification center (bell + unread count) **and** a live Reverb toast. No email. |
| Mention picker | `quill-mention` npm package (v6, Quill 2 compatible). |
| Content storage | Sanitized HTML via `mews/purifier` (HTMLPurifier). Rendered with `v-html`. |
| Mention extraction | Server parses the **sanitized** HTML for `span[data-id]`. Server is the source of truth — client-sent mention arrays are not trusted. |
| Mentionable targets | Active **Users** only (team members), not Customers. |
| Notification-center placement | Bell in the layout header. |
| Edit semantics | Re-sync mentions on edit; notify **only newly added** users. |
| Self-mention | Never notifies the author. |

## New dependencies (approved)

- composer: `laravel/reverb`, `mews/purifier`
- npm: `laravel-echo`, `pusher-js`, `quill-mention`

`laravel/reverb`, `laravel-echo`, and `pusher-js` are installed/scaffolded via
`php artisan install:broadcasting` (Phase 3).

## Data model

### `mentions` pivot (new migration)

- `id`
- `note_id` (FK → `notes`, cascade on delete)
- `user_id` (FK → `users`, cascade on delete)
- `timestamps`
- unique(`note_id`, `user_id`)

Purpose: persisted record of who is mentioned in each note. Enables rendering
"mentioned" state and computing the new-mentions diff on edit.

`Note` gains `mentionedUsers(): BelongsToMany` (→ `User` through `mentions`).

### `notifications` table (new migration)

Standard Laravel database-notifications table (`php artisan make:notifications-table`).

### `notes.content`

Widened from its current text type to `longText` (HTML is larger than plain
text). Existing plain-text rows render unchanged through `v-html` (no tags
present means nothing to interpret).

## Backend flow

### Store / update (`NoteController`)

1. `NoteRequest` validates input (`title`, `type`, `content`, `model_type`, `model_id`).
2. Content is sanitized with Purifier using a dedicated config allowlist:
   `p, br, strong, em, u, s, ol, ul, li, a[href], span[class|data-id]`
   (strips `<script>`, `on*` handlers, disallowed tags/attributes).
3. The note is saved with the sanitized HTML.
4. `NoteMentionService` (single responsibility):
   - parses the sanitized HTML for `span[data-id]`,
   - resolves IDs to existing users, drops invalid IDs, dedupes, removes the author (self),
   - syncs the `mentions` pivot,
   - returns the set of **newly added** users (those not previously mentioned on this note).
5. Each newly added user is sent a `UserMentionedInNote` notification.

### `UserMentionedInNote` notification

Implements `toDatabase` + `toBroadcast` so a single notification both persists
and pushes live. Payload:

- mentioner name
- note id
- parent label + URL (the note's `notable` show page)
- short content snippet (plain-text, truncated)

## Real-time (Reverb)

- `php artisan install:broadcasting` scaffolds Reverb, `config/reverb.php`,
  `config/broadcasting.php`, the Echo bootstrap, and `.env` keys.
- Private channel `App.Models.User.{id}`, authorized in `routes/channels.php`
  so a user may only listen to their own channel.
- `resources/js/echo.js` initializes Echo against Reverb (pusher protocol),
  imported from `app.js`.

## Frontend

### `RichTextEditor.vue` (new, reusable)

- Wraps Quill + quill-mention.
- `v-model`-compatible (emits HTML on change).
- quill-mention `source` calls `GET /users/mentionable?q=` returning
  `{ id, full_name, avatar_url }`, scoped to users and auth-guarded.
- Inserts mentions as `span.mention[data-id]` tokens.

### `NoteForm.vue`

- The `<textarea>` for content is replaced by `<RichTextEditor v-model="form.content" />`.
- Error display and submit flow unchanged.

### `NoteItem.vue`

- Content rendered via `v-html` inside a `.prose` wrapper.
- Mention tokens styled as chips.

### `NotificationBell.vue` (new, in layout header)

- Unread count badge.
- Dropdown list of notifications; each links to the note's parent page.
- Mark-one-read and mark-all-read via a small `NotificationController`.
- Initial unread list arrives as an Inertia **shared prop** (no polling).
- Echo pushes new notifications into a reactive store live and triggers a toast.

## Edit / self-mention semantics

- Editing a note re-syncs the `mentions` pivot.
- Only **newly added** users are notified — already-mentioned users are not
  re-notified.
- Self-mentions never notify the author.

## Error handling

- Invalid / non-existent mentioned user IDs are silently dropped (server is the
  source of truth; mentions are derived from sanitized content).
- Purifier strips anything outside the allowlist; a note whose content sanitizes
  to empty fails validation like any empty content.
- Channel authorization rejects any user attempting to listen to another user's
  channel.
- If broadcasting is unavailable, database notifications still persist (the
  notification center degrades gracefully without the live toast).

## Testing

Feature tests (Pest, `Notification::fake()`):

- Mentioning a user creates a `mentions` row and sends `UserMentionedInNote`.
- The author is **not** notified when self-mentioned.
- Editing a note notifies **only** newly added mentions.
- Purifier strips `<script>` / `on*` handlers from stored content.
- `GET /users/mentionable` is scoped to users and requires auth.
- Channel authorization: owner allowed, non-owner rejected.
- `mentions` pivot is synced correctly on create and edit.

Browser smoke test:

- The note form's editor mounts and submits without JavaScript errors.

## Build phases

1. **Rich text** — Quill + quill-mention editor, Purifier storage + config, `v-html` rendering, `content` → `longText`.
2. **Mentions + DB notifications** — `mentions` pivot + relation, `NoteMentionService`, `UserMentionedInNote` (database channel), `NotificationController`, `NotificationBell.vue`, shared-prop wiring.
3. **Reverb real-time** — `install:broadcasting`, `routes/channels.php`, `echo.js`, broadcast channel on the notification, live toast.

Each phase ends green (its tests pass) before the next begins.
