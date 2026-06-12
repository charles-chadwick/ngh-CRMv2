<?php

namespace App\Models;

use App\Enum\CustomerRole;
use App\Models\Concerns\HasSearch;
use App\Models\Concerns\WithAvatar;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'role',
    'title',
    'prefix',
    'first_name',
    'last_name',
    'suffix',
    'email',
    'password',
    'company_id',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class Customer extends Authenticatable implements HasMedia
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasSearch, InteractsWithMedia, Notifiable, WithAvatar;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => CustomerRole::class,
        ];
    }

    protected $appends = [
        'full_name',
        'initials',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWithSearch($query, $search)
    {
        $term = preg_replace('/[^a-zA-Z0-9]/', '', $search);

        return $query->when($search, function ($query) use ($term) {
            $query->where('first_name_normalized', 'like', "{$term}%")
                ->orWhere('last_name_normalized', 'like', "{$term}%")
                ->orWhere('email', 'like', "{$term}%");
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 192, 192)
            ->nonQueued();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return $this->first_name[0].$this->last_name[0];
    }
}
