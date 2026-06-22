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
