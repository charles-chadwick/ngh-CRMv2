<?php

namespace App\Models\Concerns;

use App\Models\Topic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Adds a polymorphic `topics` relationship so a User or Customer can
 * participate in many private messaging {@see Topic}s.
 */
trait WithTopics
{
    public function topics(): MorphToMany
    {
        return $this->morphToMany(Topic::class, 'participantable', 'topic_participant')
            ->withPivot('last_read_at')
            ->withTimestamps()
            ->latest('topics.updated_at');
    }

    /**
     * Paginate the participant's topics for the inbox, eager-loading the latest
     * message and the participants needed to render each row.
     *
     * @return LengthAwarePaginator<int, Topic>
     */
    public function paginatedTopics(int $perPage = 15): LengthAwarePaginator
    {
        return $this->topics()
            ->with([
                'messages' => fn ($query) => $query->latest()->limit(1),
                'users' => fn ($query) => $query->withAvatar(),
                'customers' => fn ($query) => $query->withAvatar(),
            ])
            ->paginate($perPage, ['*'], 'topics_page')
            ->withQueryString();
    }
}
