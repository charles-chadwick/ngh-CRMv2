# Customer Messages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reshape messaging so a Message is a polymorphic child of a customer that looks and behaves exactly like Notes/Contacts on the customer page, with the single addition of real-time Reverb broadcasts; delete the entire Topics inbox layer.

**Architecture:** A `Message` morphs to a `messageable` (Customer) and to a polymorphic `author` (User or Customer, set to the current actor). CRUD mirrors `NoteController` (whitelist resolve → `back()`). `MessageService::post()` sanitizes, attributes the author, and broadcasts `MessagePosted` on a per-customer private channel. The customer-page `MessagesSection` listens on that channel and does a partial Inertia reload so the server stays the source of truth.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Pest 4, Laravel Reverb (Echo + Pusher JS), Tailwind v4.

## Global Constraints

- Naming: `snake_case` variables, `camelCase` methods/functions, `TitleCase` classes (per CLAUDE.md).
- PHP: curly braces always; constructor property promotion; explicit return types and param type hints; PHPDoc over inline comments.
- Seeders/factories: reference only existing users/customers — never create users/customers to satisfy a relationship; pull with `User::inRandomOrder()->first()` / collections, leaving `null` when none exist.
- Run `vendor/bin/pint --dirty --format agent` after PHP edits, before each commit.
- Tests: Pest; run with `php artisan test --compact`. Every change is covered by a test.
- Two intentional deviations from notes: (1) Messages broadcast via Reverb on store; (2) edit/delete are author-only.
- Reverb broadcast is on `store` only; edits/deletes reflect on next load.
- Vue components must have a single root element.

---

## File Structure

**Backend — create:**
- `app/Models/Concerns/WithMessages.php` — `messages()` morphMany + `paginatedMessages()`.
- `database/seeders/MessageSeeder.php` — per-customer demo messages (two-sided).
- `tests/Feature/MessageModelTest.php`, `tests/Feature/MessageRequestTest.php`.

**Backend — rewrite:**
- `database/migrations/2026_06_21_215215_create_messages_table.php` — `messageable` + `author` morphs.
- `app/Models/Message.php` — `messageable()` + `author()` morphs; drop `topic()`.
- `app/Models/Customer.php` — swap `WithTopics`→`WithMessages`; remove `conversation()` + old `paginatedMessages()`.
- `app/Models/User.php` — remove `WithTopics`.
- `app/Http/Requests/MessageRequest.php` — `model_type`/`model_id`/`content`.
- `app/Services/MessageService.php` — `post(Model $messageable, Model $author, string $content)`.
- `app/Http/Controllers/MessageController.php` — polymorphic store/update/destroy, author authorization.
- `app/Events/MessagePosted.php` — `customers.{id}` channel.
- `routes/web.php` — collapse message routes; remove topic + customer-message routes.
- `routes/channels.php` — `customers.{customer}` channel; remove topic channel.
- `database/factories/MessageFactory.php` — `forMessageable()` + `byAuthor()`.
- `database/seeders/DatabaseSeeder.php` — `TopicSeeder`→`MessageSeeder`.
- `tests/Feature/MessageControllerTest.php` — rewrite.

**Backend — delete:**
- `app/Models/Topic.php`, `app/Models/Concerns/WithTopics.php`
- `app/Http/Controllers/CustomerMessageController.php`, `app/Http/Controllers/TopicController.php`
- `app/Http/Requests/TopicRequest.php`
- `database/seeders/TopicSeeder.php`, `database/factories/TopicFactory.php`
- `database/migrations/2026_06_21_215213_create_topics_table.php`
- `database/migrations/2026_06_21_215214_create_topic_participant_table.php`
- `database/migrations/2026_06_21_223629_add_customer_id_to_topics_table.php`
- `tests/Feature/CustomerMessageTest.php`, `tests/Feature/TopicControllerTest.php`

**Frontend — rename `resources/js/Components/Topics/` → `resources/js/Components/Messages/`, then:**
- Rewrite `MessagesSection.vue` (polymorphic props + `useConfirm` + Echo listener).
- Rewrite `MessageForm.vue` (polymorphic props + `messages.store`).
- Keep `MessageItem.vue`, `AddMessageButton.vue` (moved only).
- Delete `ParticipantPicker.vue`, `StartTopicButton.vue`, `MessageComposer.vue`.

**Frontend — other:**
- `resources/js/Pages/Customers/Show.vue` — update imports + props.
- `resources/js/Layouts/AppLayout.vue` — remove the Messages nav link.
- Delete `resources/js/Pages/Topics/` (Index.vue, Show.vue, Form.vue).

**Kept untouched:** `ActAs` middleware, `ActAsController`, `ActAsSwitcher.vue`, `echo.js`.

---

## Task 1: Backend rewrite (data, CRUD, broadcast, seeders)

This task replaces the entire server-side messaging layer atomically so the app boots and the suite is green at the end. Because the DB schema changes, the model, request, service, controller, event, factory, seeder, and tests must all move together.

**Files:** see "Backend" lists above.

**Interfaces:**
- Produces: `Message::messageable(): MorphTo`, `Message::author(): MorphTo`; `WithMessages::messages(): MorphMany`, `WithMessages::paginatedMessages(int $perPage = 3): LengthAwarePaginator` (pagination key `messages_page`); `MessageService::post(Model $messageable, Model $author, string $content): Message`; routes `messages.store`/`messages.update`/`messages.destroy`; broadcast channel `private-customers.{id}` event `.MessagePosted`; `MessageFactory::forMessageable(Model)`, `MessageFactory::byAuthor(Model)`.
- Consumes: existing `NoteContentSanitizer::clean(string): string`, `WithActivity`, `Customer`/`User` factories, `ActAs` middleware (skipped in console so `actingAs()` holds).

- [ ] **Step 1: Rewrite the messages migration**

Replace `database/migrations/2026_06_21_215215_create_messages_table.php` body's `up()`:

```php
public function up(): void
{
    Schema::create('messages', function (Blueprint $table): void {
        $table->id();
        $table->morphs('messageable');
        $table->morphs('author');
        $table->text('content');
        $table->timestamps();
        $table->softDeletes();
    });
}
```

- [ ] **Step 2: Delete the three topic migrations**

```bash
rm database/migrations/2026_06_21_215213_create_topics_table.php \
   database/migrations/2026_06_21_215214_create_topic_participant_table.php \
   database/migrations/2026_06_21_223629_add_customer_id_to_topics_table.php
```

- [ ] **Step 3: Rewrite the Message model**

Replace `app/Models/Message.php`:

```php
<?php

namespace App\Models;

use App\Models\Concerns\WithActivity;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'content',
])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, SoftDeletes, WithActivity;

    /**
     * The parent model this message belongs to (customer, etc.).
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user or customer who wrote this message.
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
```

- [ ] **Step 4: Create the WithMessages concern**

Create `app/Models/Concerns/WithMessages.php`:

```php
<?php

namespace App\Models\Concerns;

use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds a polymorphic `messages` relationship backed by the {@see Message} model.
 */
trait WithMessages
{
    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable')->latest();
    }

    /**
     * Paginate the model's messages for display, preserving the current query string.
     *
     * @return LengthAwarePaginator<int, Message>
     */
    public function paginatedMessages(int $perPage = 3): LengthAwarePaginator
    {
        return $this->messages()
            ->with('author')
            ->paginate($perPage, ['*'], 'messages_page')
            ->withQueryString();
    }
}
```

- [ ] **Step 5: Wire Customer to WithMessages**

In `app/Models/Customer.php`: replace the `use App\Models\Concerns\WithTopics;` import with `use App\Models\Concerns\WithMessages;`; in the trait `use` list (line ~45) replace `WithTopics` with `WithMessages`; **delete** the `conversation()` method and the old `paginatedMessages()` method (the block around lines 71-90, plus the now-unused `Topic`/`HasOne`/`LengthAwarePaginator` imports if they become unused — keep `LengthAwarePaginator` only if still referenced). Read the file first to get exact ranges.

- [ ] **Step 6: Remove WithTopics from User**

In `app/Models/User.php`: remove the `use App\Models\Concerns\WithTopics;` import and remove `WithTopics` from the trait `use` list (line ~40).

- [ ] **Step 7: Write the Message model test**

Create `tests/Feature/MessageModelTest.php`:

```php
<?php

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches a message to a customer and a user author', function () {
    $customer = Customer::factory()->create();
    $author = User::factory()->create();

    $message = Message::factory()->forMessageable($customer)->byAuthor($author)->create();

    expect($message->messageable->is($customer))->toBeTrue()
        ->and($message->author->is($author))->toBeTrue();
});

it('can be authored by a customer', function () {
    $customer = Customer::factory()->create();

    $message = Message::factory()->forMessageable($customer)->byAuthor($customer)->create();

    expect($message->author)->toBeInstanceOf(Customer::class);
});
```

- [ ] **Step 8: Rewrite the MessageFactory**

Replace `database/factories/MessageFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'messageable_type' => (new Customer)->getMorphClass(),
            'messageable_id' => Customer::factory(),
            'author_type' => (new User)->getMorphClass(),
            'author_id' => User::factory(),
            'content' => '<p>'.$this->faker->paragraph().'</p>',
        ];
    }

    /**
     * Attach the message to the given messageable model (customer, etc.).
     */
    public function forMessageable(Model $messageable): static
    {
        return $this->state(fn (array $attributes): array => [
            'messageable_type' => $messageable->getMorphClass(),
            'messageable_id' => $messageable->getKey(),
        ]);
    }

    /**
     * Attribute the message to the given user or customer author.
     */
    public function byAuthor(Model $author): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_type' => $author->getMorphClass(),
            'author_id' => $author->getKey(),
        ]);
    }
}
```

- [ ] **Step 9: Migrate fresh and run the model test**

```bash
php artisan migrate:fresh
php artisan test --compact --filter=MessageModelTest
```
Expected: 2 passed. (`migrate:fresh` runs without the topic migrations and without seeders.)

- [ ] **Step 10: Rewrite the MessageRequest**

Replace `app/Http/Requests/MessageRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $is_update = $this->route('message') !== null;

        return [
            'model_type' => [
                Rule::requiredIf(! $is_update),
                'string',
                Rule::in(['customer']),
            ],
            'model_id' => [
                Rule::requiredIf(! $is_update),
                'integer',
            ],
            'content' => [
                'required',
                'string',
            ],
        ];
    }
}
```

- [ ] **Step 11: Rewrite the MessageService**

Replace `app/Services/MessageService.php`:

```php
<?php

namespace App\Services;

use App\Events\MessagePosted;
use App\Models\Message;
use Illuminate\Database\Eloquent\Model;

/**
 * Creates messages on a messageable model: sanitizes content, attributes the
 * author, and broadcasts the new message to others on the model's channel.
 */
class MessageService
{
    public function __construct(private NoteContentSanitizer $sanitizer) {}

    /**
     * Post a message authored by the given user or customer to the messageable model.
     */
    public function post(Model $messageable, Model $author, string $content): Message
    {
        $message = $messageable->messages()->make([
            'content' => $this->sanitizer->clean($content),
        ]);

        $message->author()->associate($author);
        $message->save();

        broadcast(new MessagePosted($message))->toOthers();

        return $message;
    }
}
```

- [ ] **Step 12: Re-channel the MessagePosted event**

In `app/Events/MessagePosted.php`: change `broadcastOn()` to use the customer channel, and update `broadcastWith()` to emit `messageable_id` instead of `topic_id`:

```php
public function broadcastOn(): array
{
    return [
        new PrivateChannel('customers.'.$this->message->messageable_id),
    ];
}
```

and in `broadcastWith()` replace the `'topic_id' => $this->message->topic_id,` line with:

```php
'messageable_id' => $this->message->messageable_id,
```

- [ ] **Step 13: Rewrite the MessageController**

Replace `app/Http/Controllers/MessageController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Customer;
use App\Models\Message;
use App\Services\MessageService;
use App\Services\NoteContentSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    /**
     * Whitelist of models that may receive messages, keyed by the
     * `model_type` value accepted from the client.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $models = [
        'customer' => Customer::class,
    ];

    /**
     * Store a message against the given messageable model.
     */
    public function store(MessageRequest $request, MessageService $service): RedirectResponse
    {
        $validated = $request->validated();

        $messageable = $this->resolveModel($validated['model_type'], $validated['model_id']);

        $service->post($messageable, auth()->user(), $validated['content']);

        return back();
    }

    /**
     * Update an existing message authored by the current actor.
     */
    public function update(MessageRequest $request, Message $message): RedirectResponse
    {
        $this->authorizeAuthor($message);

        $message->update([
            'content' => app(NoteContentSanitizer::class)->clean($request->validated()['content']),
        ]);

        return back();
    }

    /**
     * Remove a message authored by the current actor.
     */
    public function destroy(Message $message): RedirectResponse
    {
        $this->authorizeAuthor($message);

        $message->delete();

        return back();
    }

    /**
     * Abort unless the current actor is the message's author.
     */
    protected function authorizeAuthor(Message $message): void
    {
        $actor = auth()->user();

        abort_unless(
            $actor !== null
                && $message->author_type === $actor->getMorphClass()
                && (int) $message->author_id === (int) $actor->getKey(),
            403
        );
    }

    /**
     * Resolve the target messageable model from the whitelist.
     */
    protected function resolveModel(string $type, int|string $id): Model
    {
        return $this->models[$type]::findOrFail($id);
    }
}
```

- [ ] **Step 14: Update routes/web.php**

Remove the `CustomerMessageController` and `TopicController` imports. Remove the topic routes block and the `customers/{customer}/messages` route. The message routes block becomes:

```php
Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
Route::put('messages/{message}', [MessageController::class, 'update'])->name('messages.update');
Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
```

Delete these lines if present:
```php
Route::get('topics/participants', [TopicController::class, 'participants'])->name('topics.participants');
Route::resource('topics', TopicController::class)->only(['index', 'create', 'show', 'store']);
Route::post('customers/{customer}/messages', [CustomerMessageController::class, 'store'])->name('customers.messages.store');
```

- [ ] **Step 15: Update routes/channels.php**

Replace `app/Models/Topic;` import with `use App\Models\Customer;`, remove the `topics.{topic}` channel, and add:

```php
Broadcast::channel('customers.{customer}', function ($actor, Customer $customer): bool {
    if ($actor instanceof Customer) {
        return (int) $actor->id === (int) $customer->id;
    }

    return true;
}, ['guards' => ['web', 'customer']]);
```

Keep the existing `App.Models.User.{id}` channel.

- [ ] **Step 16: Delete dead backend topic files**

```bash
rm app/Models/Topic.php \
   app/Models/Concerns/WithTopics.php \
   app/Http/Controllers/CustomerMessageController.php \
   app/Http/Controllers/TopicController.php \
   app/Http/Requests/TopicRequest.php \
   database/factories/TopicFactory.php \
   tests/Feature/CustomerMessageTest.php \
   tests/Feature/TopicControllerTest.php
```

- [ ] **Step 17: Rewrite the MessageController test**

Replace `tests/Feature/MessageControllerTest.php`:

```php
<?php

use App\Events\MessagePosted;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->author);
});

it('stores a message against a customer and attributes the author', function () {
    $this->post(route('messages.store'), [
        'model_type' => 'customer',
        'model_id' => $this->customer->id,
        'content' => '<p>Hello there.</p>',
    ])->assertRedirect();

    $message = Message::firstOrFail();

    expect($message->messageable->is($this->customer))->toBeTrue()
        ->and($message->author->is($this->author))->toBeTrue()
        ->and($message->content)->toContain('Hello there.');
});

it('broadcasts MessagePosted on the customer channel', function () {
    Event::fake([MessagePosted::class]);

    $this->post(route('messages.store'), [
        'model_type' => 'customer',
        'model_id' => $this->customer->id,
        'content' => '<p>Ping.</p>',
    ])->assertRedirect();

    Event::assertDispatched(MessagePosted::class, function (MessagePosted $event) {
        $channels = $event->broadcastOn();

        return $channels[0] instanceof PrivateChannel
            && $channels[0]->name === 'private-customers.'.$this->customer->id;
    });
});

it('lets a customer author a message (two-sided)', function () {
    $this->actingAs($this->customer, 'customer')->post(route('messages.store'), [
        'model_type' => 'customer',
        'model_id' => $this->customer->id,
        'content' => '<p>Reply from the customer.</p>',
    ])->assertRedirect();

    expect(Message::firstOrFail()->author)->toBeInstanceOf(Customer::class);
});

it('rejects an unknown model type', function () {
    $this->post(route('messages.store'), [
        'model_type' => 'lead',
        'model_id' => $this->customer->id,
        'content' => '<p>Nope.</p>',
    ])->assertSessionHasErrors('model_type');

    expect(Message::count())->toBe(0);
});

it('updates the actor’s own message', function () {
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($this->author)->create();

    $this->put(route('messages.update', $message->id), [
        'content' => '<p>Edited.</p>',
    ])->assertRedirect();

    expect($message->fresh()->content)->toContain('Edited.');
});

it('forbids editing another actor’s message', function () {
    $other = User::factory()->create();
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($other)->create();

    $this->put(route('messages.update', $message->id), [
        'content' => '<p>Hijack.</p>',
    ])->assertForbidden();
});

it('deletes the actor’s own message', function () {
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($this->author)->create();

    $this->delete(route('messages.destroy', $message->id))->assertRedirect();

    expect($message->fresh()->trashed())->toBeTrue();
});

it('forbids deleting another actor’s message', function () {
    $other = User::factory()->create();
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($other)->create();

    $this->delete(route('messages.destroy', $message->id))->assertForbidden();

    expect($message->fresh()->trashed())->toBeFalse();
});

it('includes the customer messages on the show page', function () {
    Message::factory()->forMessageable($this->customer)->byAuthor($this->author)->create();

    $this->get(route('customers.show', $this->customer->id))
        ->assertInertia(fn ($page) => $page
            ->component('Customers/Show')
            ->has('messages.data', 1));
});
```

- [ ] **Step 18: Write the MessageRequest test**

Create `tests/Feature/MessageRequestTest.php`:

```php
<?php

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actor = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->actor);
});

it('requires the core message fields on create', function () {
    $this->post(route('messages.store'), [])
        ->assertSessionHasErrors(['model_type', 'model_id', 'content']);
});

it('passes with a complete create payload', function () {
    $this->post(route('messages.store'), [
        'model_type' => 'customer',
        'model_id' => $this->customer->id,
        'content' => '<p>Valid.</p>',
    ])->assertSessionHasNoErrors()->assertRedirect();
});

it('does not require a model when updating an existing message', function () {
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($this->actor)->create();

    $this->put(route('messages.update', $message->id), [
        'content' => '<p>Updated body.</p>',
    ])->assertSessionHasNoErrors()->assertRedirect();
});
```

- [ ] **Step 19: Create the MessageSeeder**

Create `database/seeders/MessageSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        echo "\nAdding Messages\n";

        Customer::all()->each(function (Customer $customer) use ($users): void {
            $this->seedMessagesFor($customer, $users);
            echo '.';
        });

        // Clear the causer so it doesn't leak into subsequent seeders.
        app(CauserResolver::class)->setCauser(null);

        echo "\n";
    }

    /**
     * Seed a handful of two-sided messages for the customer.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedMessagesFor(Customer $customer, Collection $users): void
    {
        $message_count = rand(0, 5);

        for ($i = 0; $i < $message_count; $i++) {
            $author = fake()->boolean(40) ? $customer : $users->random();
            $created_at = fake()->dateTimeBetween($customer->created_at);

            app(CauserResolver::class)->setCauser($author instanceof User ? $author : null);

            Message::factory()
                ->forMessageable($customer)
                ->byAuthor($author)
                ->create(['created_at' => $created_at, 'updated_at' => $created_at]);
        }
    }
}
```

- [ ] **Step 20: Swap the seeder in DatabaseSeeder and delete TopicSeeder**

In `database/seeders/DatabaseSeeder.php` replace `TopicSeeder::class,` with `MessageSeeder::class,`. Then:

```bash
rm database/seeders/TopicSeeder.php
```

- [ ] **Step 21: Pint, migrate fresh + seed, full suite**

```bash
vendor/bin/pint --dirty --format agent
php artisan migrate:fresh --seed
php artisan test --compact
```
Expected: seeding completes (Adding Messages …); all tests pass. If any topic-referencing test or file remains, remove it.

- [ ] **Step 22: Commit**

```bash
git add -A
git commit -m "feat: messages as polymorphic customer children with reverb broadcast

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: Frontend — customer-page section + Reverb listener, remove inbox

**Files:** rename `resources/js/Components/Topics/` → `resources/js/Components/Messages/`; rewrite `MessagesSection.vue`, `MessageForm.vue`; edit `Customers/Show.vue`, `AppLayout.vue`; delete `Pages/Topics/`, `ParticipantPicker.vue`, `StartTopicButton.vue`, `MessageComposer.vue`.

**Interfaces:**
- Consumes: `messages.store`/`messages.update`/`messages.destroy` routes, `messages.data` paginator prop, `private-customers.{id}` channel event `.MessagePosted` (from Task 1); `useConfirm` (`@/composables/useConfirm.js`); `window.Echo` (`resources/js/echo.js`); `RichTextEditor`, `Avatar`, `Button`, `Card`, `useDate`.
- Produces: `MessagesSection` props `messageable_type`, `messageable_id`, `messages`, model `showForm`; `MessageForm` props `messageable_type`, `messageable_id`, `message`.

- [ ] **Step 1: Rename the components directory**

```bash
git mv resources/js/Components/Topics resources/js/Components/Messages
```

- [ ] **Step 2: Delete the topic-only components**

```bash
rm resources/js/Components/Messages/ParticipantPicker.vue \
   resources/js/Components/Messages/StartTopicButton.vue \
   resources/js/Components/Messages/MessageComposer.vue
```

- [ ] **Step 3: Delete the Topics pages**

```bash
rm -r resources/js/Pages/Topics
```

- [ ] **Step 4: Rewrite MessagesSection.vue**

Replace `resources/js/Components/Messages/MessagesSection.vue`:

```vue
<!--
  MessagesSection
  ---------------
  The customer-page conversation, presented exactly like the notes section: an
  inline add/edit form plus a list of message cards. Mutations route through the
  polymorphic message endpoints; the parent owns the add-form visibility. New
  messages from other actors arrive over Reverb and trigger a partial reload, so
  the server stays the source of truth.
-->
<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import MessageItem from "@/Components/Messages/MessageItem.vue";
import MessageForm from "@/Components/Messages/MessageForm.vue";
import { useConfirm } from '@/composables/useConfirm.js'

const props = defineProps({
  messageable_type: { type: String, required: true },
  messageable_id: { type: [Number, String], required: true },
  messages: { type: Object, required: true },
})

const showForm = defineModel('showForm', { type: Boolean, default: false })
const editingId = ref(null)

const onSaved = () => {
  showForm.value = false
  editingId.value = null
}

const startEdit = (message) => {
  showForm.value = false
  editingId.value = message.id
}

const confirm = useConfirm()

const removeMessage = async (message) => {
  if (await confirm({
    title: 'Delete this message?',
    message: 'This action cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'destructive',
  })) {
    router.delete(route('messages.destroy', message.id), { preserveScroll: true })
  }
}

// Real-time: when another actor posts on this customer's channel, refetch just
// the messages prop. `toOthers()` means the poster never receives this.
const channel = `customers.${props.messageable_id}`

onMounted(() => {
  if (typeof window === 'undefined' || !window.Echo) {
    return
  }
  window.Echo.private(channel).listen('.MessagePosted', () => {
    router.reload({ only: ['messages'], preserveScroll: true })
  })
})

onUnmounted(() => {
  if (typeof window !== 'undefined' && window.Echo) {
    window.Echo.leave(channel)
  }
})
</script>

<template>
  <section>
    <MessageForm
        v-if="showForm"
        :messageable_type="messageable_type"
        :messageable_id="messageable_id"
        class="mb-4"
        @saved="onSaved"
        @cancel="showForm = false"
    />

    <div
        v-if="messages.data.length"
        class="space-y-3"
    >
      <template
          v-for="message in messages.data"
          :key="message.id"
      >
        <MessageForm
            v-if="editingId === message.id"
            :messageable_type="messageable_type"
            :messageable_id="messageable_id"
            :message="message"
            @saved="onSaved"
            @cancel="editingId = null"
        />
        <MessageItem
            v-else
            :message="message"
            editable
            @edit="startEdit"
            @remove="removeMessage"
        />
      </template>
    </div>
    <p
        v-else
        class="text-sm text-muted-foreground"
    >
      No messages yet.
    </p>
  </section>
</template>
```

- [ ] **Step 5: Rewrite MessageForm.vue**

Replace `resources/js/Components/Messages/MessageForm.vue`:

```vue
<!--
  MessageForm
  -----------
  Inline composer matching the note form: a rich-text body and Save/Cancel.
  Creating posts to messages.store with the polymorphic owner; editing puts to
  messages.update. No subject/type — a message is just content.
-->
<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from "@/Components/UI/button/index.js";
import { Card, CardContent } from "@/Components/UI/card/index.js";
import RichTextEditor from '@/Components/RichTextEditor.vue'

const props = defineProps({
  messageable_type: { type: String, required: true },
  messageable_id: { type: [Number, String], required: true },
  message: { type: Object, default: null },
})

const emits = defineEmits(['saved', 'cancel'])

const form = useForm({
  model_type: props.messageable_type,
  model_id: props.messageable_id,
  content: props.message?.content || '',
})

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      if (!props.message) {
        form.reset('content')
      }
      emits('saved')
    },
  }

  if (props.message) {
    form.put(route('messages.update', props.message.id), options)
  } else {
    form.post(route('messages.store'), options)
  }
}
</script>

<template>
  <Card>
    <CardContent>
      <form
          class="space-y-3"
          @submit.prevent="submit"
      >
        <div class="space-y-2">
          <RichTextEditor
              id="message_content"
              v-model="form.content"
          />
          <p
              v-if="form.errors.content"
              class="text-sm text-destructive"
          >
            {{ form.errors.content }}
          </p>
        </div>

        <div class="flex justify-end gap-3">
          <Button
              type="button"
              variant="ghost"
              size="sm"
              @click="emits('cancel')"
          >
            Cancel
          </Button>
          <Button
              type="submit"
              size="sm"
              :disabled="form.processing"
          >
            {{ message ? 'Update Message' : 'Save Message' }}
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
```

- [ ] **Step 6: Update MessageItem import path (verify)**

`MessageItem.vue` and `AddMessageButton.vue` moved with the directory and need no content change. Confirm `MessageItem.vue` still imports only `@/Components/Avatar.vue`, `@/Components/UI/button/index.js`, `@/Composables/useDate.js` (all unaffected by the rename). No edit needed unless an import points at the old `Topics` path — grep to be sure:

```bash
grep -rn "Components/Topics\|Pages/Topics" resources/js
```
Expected: no results after Step 7.

- [ ] **Step 7: Update Customers/Show.vue**

In `resources/js/Pages/Customers/Show.vue`: change the two imports
```js
import MessagesSection from "@/Components/Topics/MessagesSection.vue";
import AddMessageButton from "@/Components/Topics/AddMessageButton.vue";
```
to
```js
import MessagesSection from "@/Components/Messages/MessagesSection.vue";
import AddMessageButton from "@/Components/Messages/AddMessageButton.vue";
```
and change the `<MessagesSection>` usage props from
```vue
:customer_id="customer.id"
```
to
```vue
messageable_type="customer"
:messageable_id="customer.id"
```
(leave `v-model:show-form` and `:messages` as-is).

- [ ] **Step 8: Remove the Messages nav link**

In `resources/js/Layouts/AppLayout.vue` delete the line:
```vue
<li><a :href="route('topics.index')" class="hover:text-primary">Messages</a></li>
```

- [ ] **Step 9: Build the frontend**

```bash
npm run build
```
Expected: build succeeds with no unresolved-import errors (this is the real check that the directory rename and Show.vue/AppLayout edits are consistent — a stale `route('topics.index')` or `Components/Topics/...` import would fail here).

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat: customer-page messages section with reverb; remove topics inbox

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Manual real-time verification

Automated tests cover CRUD, authorization, validation, and that the broadcast is dispatched. The live Echo path needs the dev stack running and two browser sessions; this is a manual checkpoint.

**Files:** none (verification only).

- [ ] **Step 1: Start the stack**

Run (per project convention) `composer run dev` (or `npm run dev` + `php artisan reverb:start` + `php artisan serve`). Confirm `BROADCAST_CONNECTION=reverb` and the `VITE_REVERB_*` vars are set; if `BROADCAST_CONNECTION` is `null`/`log`, real-time won't reach the browser. Ask the user to start it if an interactive login/long-running process is needed.

- [ ] **Step 2: Open one customer in two sessions**

Open `customers.show` for the same customer in two browser profiles. Use the Act As switcher to make one session the customer and the other a staff user.

- [ ] **Step 3: Post and observe**

Post a message in session A. Confirm it appears in session B within ~1s **without a manual refresh** (the `MessagePosted` listener triggers a partial reload of `messages`), and that the new card shows the correct author (avatar + name). Confirm the poster's own session shows it via the form response (not a double reload).

- [ ] **Step 4: Section parity check**

Confirm the Messages section visually matches Notes/Contacts (card, add button, inline form, paginated list, footer attribution), that edit/delete appear only on the actor's own messages, that delete prompts the ConfirmDialog, and that there is no "Messages" link in the nav bar.

---

## Self-Review

**Spec coverage:**
- Data model mirrors Note (polymorphic `messageable`) → Task 1 Steps 1,3,4,5.
- Two-sided author morph → Task 1 Steps 3,8; tested Step 17 ("two-sided"), Step 7.
- CRUD mirrors NoteController, `back()`, author-only edit/delete → Task 1 Step 13; tested Step 17.
- `MessageRequest` shape → Step 10; tested Step 18.
- `MessageService` sanitize + broadcast `->toOthers()` → Step 11.
- Reverb per-customer channel + channel auth (both guards) → Steps 12,15; dispatch tested Step 17; listener Task 2 Step 4.
- Partial-reload client strategy → Task 2 Step 4.
- Vue parity (section/form/item) + `useConfirm` → Task 2 Steps 4,5.
- Remove inbox pages, nav link, participant picker → Task 2 Steps 2,3,7,8.
- Factory/seeder (existing-users only, two-sided) + DatabaseSeeder swap → Task 1 Steps 8,19,20.
- Delete Topic/WithTopics/controllers/requests/migrations/tests → Task 1 Steps 2,16,20.
- Act As kept → untouched (noted in File Structure).
- Out-of-scope (no edit/delete broadcast, no participants) → honored.

**Placeholder scan:** none — every code/step is concrete.

**Type consistency:** `forMessageable`/`byAuthor` (factory) used consistently in Steps 7,17,18,19; `paginatedMessages` key `messages_page` matches existing `CustomerController@show` call; `MessageService::post(Model, Model, string)` signature matches the controller call in Step 13; channel name `private-customers.{id}` matches the test assertion (Step 17) and the listener `customers.${id}` (Task 2 Step 4); request keys `model_type`/`model_id`/`content` match form (`MessageForm`) and controller.
