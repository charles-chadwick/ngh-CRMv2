<?php

namespace App\Services;

use App\Events\MessagePosted;
use App\Models\Message;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Model;

/**
 * Creates messages on a topic: sanitizes content, attributes the author,
 * marks the topic read for that author, and broadcasts the new message.
 */
class MessageService
{
    public function __construct(private NoteContentSanitizer $sanitizer) {}

    /**
     * Post a message authored by the given user or customer to the topic.
     */
    public function post(Topic $topic, Model $author, string $content): Message
    {
        $message = $topic->messages()->make([
            'content' => $this->sanitizer->clean($content),
        ]);

        $message->author()->associate($author);
        $message->save();

        $topic->touch();
        $topic->markReadFor($author);

        broadcast(new MessagePosted($message))->toOthers();

        return $message;
    }
}
