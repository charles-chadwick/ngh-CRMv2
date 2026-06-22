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
