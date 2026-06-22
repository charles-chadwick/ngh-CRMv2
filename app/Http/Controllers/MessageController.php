<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Message;
use App\Models\Topic;
use App\Services\MessageService;
use App\Services\NoteContentSanitizer;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MessageController extends Controller
{
    /**
     * Post a message to an existing topic.
     */
    public function store(MessageRequest $request, MessageService $messages): RedirectResponse
    {
        $validated = $request->validated();
        $author = $request->user();

        $topic = Topic::findOrFail($validated['topic_id']);

        abort_unless($topic->hasParticipant($author), HttpResponse::HTTP_FORBIDDEN);

        $messages->post($topic, $author, $validated['content']);

        return back();
    }

    /**
     * Edit an existing message the author owns.
     */
    public function update(MessageRequest $request, Message $message): RedirectResponse
    {
        $this->authorizeAuthor($message, $request->user());

        $message->update([
            'content' => app(NoteContentSanitizer::class)->clean($request->validated()['content']),
        ]);

        return back();
    }

    /**
     * Soft-delete a message the author owns.
     */
    public function destroy(Message $message): RedirectResponse
    {
        $this->authorizeAuthor($message, auth()->user());

        $message->delete();

        return back();
    }

    /**
     * Ensure the acting participant authored the message.
     */
    protected function authorizeAuthor(Message $message, object $participant): void
    {
        $is_author = $message->author_type === $participant->getMorphClass()
            && (int) $message->author_id === (int) $participant->getKey();

        abort_unless($is_author, HttpResponse::HTTP_FORBIDDEN);
    }
}
