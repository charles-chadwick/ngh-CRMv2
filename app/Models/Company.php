<?php

namespace App\Models;

use App\Models\Concerns\WithSearch;
use App\Models\Concerns\WithAvatar;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'title',
    'company_type_id',
    'description',
])]
class Company extends Model implements HasMedia
{
    use HasFactory, WithSearch, InteractsWithMedia, WithAvatar;

    public function customers() : HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function type() : BelongsTo
    {
        return $this->belongsTo(CompanyType::class);
    }

    public function scopeWithSearch($query, $search) : void
    {
        $this->search($query, $search, [
            'title',
            'description'
        ]);
    }

    public function registerMediaCollections() : void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/svg+xml'
            ]);
    }

    public function registerMediaConversions(?Media $media = null) : void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 192, 192)
            ->nonQueued();
    }

    public function avatarCollection() : string
    {
        return 'logo';
    }
}
