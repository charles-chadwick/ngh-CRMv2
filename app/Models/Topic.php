<?php

namespace App\Models;

use App\Models\Concerns\WithActivity;
use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[Fillable([
    'title',
])]
class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory, SoftDeletes, WithActivity;

    /**
     * Messages posted to this topic, newest first.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    /**
     * The customer whose dedicated conversation this topic is, if any.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Users participating in this topic.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'participantable', 'topic_participant')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * Customers participating in this topic.
     */
    public function customers(): MorphToMany
    {
        return $this->morphedByMany(Customer::class, 'participantable', 'topic_participant')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * The combined collection of participants (users and customers).
     *
     * @return Collection<int, Model>
     */
    public function participants(): Collection
    {
        return $this->users->concat($this->customers);
    }

    /**
     * Determine whether the given user or customer participates in this topic.
     */
    public function hasParticipant(Model $participant): bool
    {
        return $this->participantPivot($participant) !== null;
    }

    /**
     * Count messages the participant has not yet read, excluding their own.
     */
    public function unreadCountFor(Model $participant): int
    {
        $pivot = $this->participantPivot($participant);

        if ($pivot === null) {
            return 0;
        }

        return $this->messages()
            ->whereNot(function ($query) use ($participant) {
                $query->where('author_type', $participant->getMorphClass())
                    ->where('author_id', $participant->getKey());
            })
            ->when($pivot->last_read_at !== null, fn ($query) => $query->where('messages.created_at', '>', $pivot->last_read_at))
            ->count();
    }

    /**
     * Mark every message in this topic as read for the given participant.
     */
    public function markReadFor(Model $participant): void
    {
        $this->participantsRelation($participant)?->updateExistingPivot($participant->getKey(), [
            'last_read_at' => now(),
        ]);
    }

    /**
     * The pivot row linking the given participant to this topic, if any.
     */
    protected function participantPivot(Model $participant): ?object
    {
        $relation = $this->participantsRelation($participant);

        return $relation?->whereKey($participant->getKey())->first()?->pivot;
    }

    /**
     * Resolve the participant relationship matching the model's type.
     */
    protected function participantsRelation(Model $participant): ?MorphToMany
    {
        return match (true) {
            $participant instanceof User => $this->users(),
            $participant instanceof Customer => $this->customers(),
            default => null,
        };
    }
}
