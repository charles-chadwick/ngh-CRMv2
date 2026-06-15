<?php

namespace App\Models\Concerns;

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
        return $query->with(['media' => function (MorphMany $query): void {
            $query->select([
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
            ])->where('collection_name', $this->avatarCollection());
        }]);
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
