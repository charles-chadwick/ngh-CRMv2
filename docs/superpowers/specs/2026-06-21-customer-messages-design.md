# Customer Messages — Design Spec

**Date:** 2026-06-21
**Status:** Approved, pending implementation plan

## Goal

Reshape the messaging feature so that **Messages function and look exactly like Notes and
Contacts** — an inline section on the customer page with add/edit/delete, pagination, and
author attribution — **with one difference: messages broadcast in real time over Reverb.**

Messages are **two-sided**: a message may be authored by a User (staff) or by the Customer.
The "Act As" impersonation tool is kept so the customer side can be exercised in dev.

The standalone Topics inbox (nav link, `/topics` Index/Show/Form pages, participant pickers,
the `Topic`/participant data model) is **removed entirely**.

## Decisions (locked)

1. **Architecture:** Mirror Note. A `Message` is a polymorphic child of the customer
   (`messageable`). No `Topic`, no participant pivot, no conversation container.
2. **Authorship:** Two-sided. `Message` has a polymorphic `author` (User or Customer), set to
   the current actor at post time. Act As is retained.
3. **Inbox:** Removed completely — nav link, Topics pages, participant picker, topic routes.

## Data model

`messages` table — polymorphic child of the customer, exactly like `notes` are `notable`:

| column | purpose |
|---|---|
| `id` | PK |
| `messageable_type` / `messageable_id` | owner (Customer for now; whitelist-extensible) |
| `author_type` / `author_id` | who wrote it — `User` or `Customer` (two-sided) |
| `content` | sanitized HTML |
| `created_at` / `updated_at` | timestamps |
| `deleted_at` | soft deletes |

`Message` model: `SoftDeletes` + `WithActivity`; `messageable()` morphTo, `author()` morphTo;
fillable `content`.

**Migrations:** the three untracked topic-era migrations (`create_topics_table`,
`create_topic_participant_table`, `add_customer_id_to_topics_table`) and the current
`create_messages_table` (which has `topic_id` + author morph) are rewritten into a **single
`create_messages_table`** carrying the `messageable` morph, the `author` morph, `content`,
timestamps, and soft deletes. Since these migrations are untracked, they are rewritten in
place; the database is rebuilt with `migrate:fresh`.

**Customer** gains a `WithMessages` concern paralleling `WithNotes`:

```php
public function messages(): MorphMany
{
    return $this->morphMany(Message::class, 'messageable')->latest();
}

public function paginatedMessages(int $perPage = 3): LengthAwarePaginator
{
    return $this->messages()
        ->with('author') // includes author avatar fields for display + broadcast
        ->paginate($perPage, ['*'], 'messages_page')
        ->withQueryString();
}
```

`WithTopics` is **deleted** and removed from both `Customer` and `User`.

## Backend CRUD — mirrors NoteController

Routes (`routes/web.php`) collapse to:

```php
Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
Route::put('messages/{message}', [MessageController::class, 'update'])->name('messages.update');
Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
```

The `customers/{customer}/messages` route and all `topics.*` routes are removed.

`MessageController`:

- `store(MessageRequest, MessageService)` — resolves the messageable from a whitelist
  (`'customer' => Customer::class`, mirroring NoteController's `resolveModel`), then calls
  `MessageService::post()`. Returns `back()`.
- `update(MessageRequest, Message)` — authorizes that the current actor **is the author**
  (403 otherwise), updates sanitized content, returns `back()`.
- `destroy(Message)` — authorizes author, soft-deletes, returns `back()`.

`MessageRequest` (mirrors `NoteRequest`):

| field | create | update |
|---|---|---|
| `model_type` | required, in `['customer']` | not required (route `{message}` present) |
| `model_id` | required, integer | not required |
| `content` | required, string | required, string |

`MessageService::post(Model $messageable, Model $author, string $content): Message`:

1. Make a message on `$messageable->messages()` with `content` sanitized via
   `NoteContentSanitizer`.
2. Associate `$author` (the current actor — `auth()->user()`, which Act As may set to a
   Customer).
3. Save.
4. `broadcast(new MessagePosted($message))->toOthers();`

**Deleted backend:** `CustomerMessageController`, `TopicController`, `TopicRequest`,
`Topic` model, `WithTopics` concern.

## Reverb — the one difference from notes

- `MessagePosted` (already `ShouldBroadcastNow`) re-channels from `topics.{id}` to a
  **per-customer private channel** `customers.{messageable_id}`. It broadcasts on `store`
  only, `->toOthers()`.
- `routes/channels.php`: replace the `topics.{topic}` channel with:

  ```php
  Broadcast::channel('customers.{customer}', function ($actor, Customer $customer): bool {
      // a Customer actor may listen only to their own thread; staff users see any customer
      if ($actor instanceof Customer) {
          return (int) $actor->id === (int) $customer->id;
      }
      return true;
  });
  ```

  Channel authorization must consider both the `web` and `customer` guards (Act As may make
  the customer guard active).
- **Client** (`MessagesSection.vue`): on mount, subscribe via
  `window.Echo.private('customers.' + messageable_id).listen('.MessagePosted', …)`; on
  unmount, `window.Echo.leave('customers.' + messageable_id)`. On receipt, run
  `router.reload({ only: ['messages'], preserveScroll: true })` — **the server stays the
  source of truth**, avoiding client-side merge/dedup bugs and keeping pagination correct.
  The poster does not reload (their form response already refreshed the list; `toOthers()`
  excludes them).

**Scope note:** Only `store` broadcasts. Edits and deletes reflect on the next load. (Not
extended to update/destroy in v1.)

## Vue components — restyled to match notes exactly

`resources/js/Components/Topics/` is renamed to `resources/js/Components/Messages/`.

- **`MessagesSection.vue`** = `NotesSection.vue` clone with props `messageable_type` /
  `messageable_id` (replacing `customer_id`), `messages`; adds `useConfirm()` on delete
  (currently missing) and the Echo listener above.
- **`MessageForm.vue`** = `NoteForm.vue` minus type/title — a single `RichTextEditor` bound to
  `content`. `useForm({ model_type, model_id, content })`; `form.post(route('messages.store'))`
  on create, `form.put(route('messages.update', message.id))` on edit. `preserveScroll`,
  resets content on create success, emits `saved`/`cancel`.
- **`MessageItem.vue`** = `NoteItem.vue` shape — content via `v-html`; footer shows **author
  avatar + name + timestamp**; edit/delete buttons render only when the current actor is the
  author. Emits `edit`/`remove`.

**Deleted Vue:** `Pages/Topics/Index.vue`, `Show.vue`, `Form.vue`;
`Components/Topics/ParticipantPicker.vue`, `StartTopicButton.vue`, `MessageComposer.vue`.

**Nav:** remove the `Messages → /topics` link from `AppLayout.vue`.

**Customer Show page:** pass `messageable_type="customer"` and `:messageable_id="customer.id"`
to `MessagesSection` (replacing `customer_id`); `messages` prop unchanged.

## Act As — kept

`ActAs` middleware, `ActAsController`, and `ActAsSwitcher.vue` are retained unchanged — they
are how the customer side is posted in dev so the conversation has two sides.

## Factories / seeders / tests

- `MessageFactory`: `forMessageable(Model)` + `byAuthor(Model)` states (drop `forTopic`);
  default `messageable` = Customer, `author` = an existing User, `content` = HTML paragraph.
- **Delete** `TopicFactory`, `TopicSeeder`. Add `MessageSeeder` modeled on `NoteSeeder`: per
  customer, create a handful of messages authored by random existing users and the customer
  itself, with `CauserResolver` attribution and backdated timestamps. Reference only existing
  users/customers (never create them). Swap `MessageSeeder` for `TopicSeeder` in
  `DatabaseSeeder`.
- Tests (replace `CustomerMessageTest`, `MessageControllerTest`, `TopicControllerTest`):
  - `MessageRequestTest` — validation, mirroring `NoteRequestTest` (core fields required;
    unknown `model_type` rejected; model not required on update).
  - `MessageControllerTest` — polymorphic store attributes the author and dispatches
    `MessagePosted` (`Event::fake`); acting-as-customer authors a customer-side message;
    `update`/`destroy` enforce author authorization (403 for non-author); invalid `model_type`
    rejected; `destroy` soft-deletes.
  - `MessageModelTest` — morph attach of `messageable` and `author`.
  - Customer-show assertion that the Inertia `messages.data` prop is present.

## Out of scope (YAGNI)

- Broadcasting edits/deletes (store only).
- Multi-party conversations, participant management, read receipts, unread counts.
- Standalone messaging inbox / cross-customer message browsing.
- Attachments, typing indicators, presence.
