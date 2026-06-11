<?php

namespace App\Models;

use App\Enum\UserRole;
use App\Models\Concerns\WithAvatar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'title',
    'type',
    'description'
])]
class Company extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, WithAvatar;

    public function customers(): Company|HasMany
    {
        return $this->hasMany(User::class)->where('role', UserRole::Customer);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 192, 192)
            ->nonQueued();
    }

    protected function avatarCollection(): string
    {
        return 'logo';
    }
}
