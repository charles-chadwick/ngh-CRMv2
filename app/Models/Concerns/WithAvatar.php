<?php

namespace App\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds `avatar_url` (thumb conversion) and `avatar_full_url` (original) accessors
 * backed by a Spatie Media Library collection, along with a `withAvatar()` scope
 * that eager loads only the media columns required to generate the conversion URLs.
 */
trait WithAvatar
{
    public function initializeWithAvatar(): void
    {
        $this->append(['avatar_url', 'avatar_full_url']);
    }

    public function scopeWithAvatar(Builder $query): Builder
    {
        return $query->with(['media' => $this->avatarMediaConstraint()]);
    }

    /**
     * Constraint that trims an avatar media relation to just the columns and
     * collection needed to build conversion URLs. Reusable by the
     * `withAvatar()` scope as well as lazy and nested `load()` calls.
     */
    public function avatarMediaConstraint(): Closure
    {
        return function (MorphMany $query): void {
            $query->select($this->mediaAvatarColumns())
                ->where('collection_name', $this->avatarCollection());
        };
    }

    /**
     * Columns required to generate avatar conversion URLs without
     * loading the full media row.
     *
     * @return list<string>
     */
    public function mediaAvatarColumns(): array
    {
        return [
            'id',
            'model_type',
            'model_id',
            'uuid',
            'collection_name',
            'file_name',
            'disk',
            'conversions_disk',
            'manipulations',
            'custom_properties',
            'generated_conversions',
            'responsive_images',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl($this->avatarCollection(), 'thumb') ?: null;
    }

    public function getAvatarFullUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl($this->avatarCollection()) ?: null;
    }

    public function avatarCollection(): string
    {
        return 'avatar';
    }
}
