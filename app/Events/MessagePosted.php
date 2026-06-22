<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagePosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('customers.'.$this->message->messageable_id),
        ];
    }

    /**
     * The event name broadcast to the client.
     */
    public function broadcastAs(): string
    {
        return 'MessagePosted';
    }

    /**
     * The payload appended live to the open thread.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $author = $this->message->author;

        return [
            'id' => $this->message->id,
            'messageable_id' => $this->message->messageable_id,
            'content' => $this->message->content,
            'created_at' => $this->message->created_at?->toIso8601String(),
            'author' => [
                'type' => $this->message->author_type,
                'id' => $this->message->author_id,
                'full_name' => $author?->full_name,
                'initials' => $author?->initials,
                'avatar_url' => $author?->avatar_url,
            ],
        ];
    }
}
