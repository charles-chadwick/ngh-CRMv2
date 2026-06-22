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
