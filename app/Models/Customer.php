<?php

namespace App\Models;

use App\Models\Concerns\WithActivity;
use App\Models\Concerns\WithAvatar;
use App\Models\Concerns\WithContacts;
use App\Models\Concerns\WithMessages;
use App\Models\Concerns\WithNotes;
use App\Models\Concerns\WithSearch;
use App\Models\Concerns\WithSort;
use App\Models\Concerns\WithTasks;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'customer_role_id',
    'title',
    'prefix',
    'first_name',
    'last_name',
    'suffix',
    'date_of_birth',
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
    use HasFactory, InteractsWithMedia, Notifiable, SoftDeletes, WithActivity, WithAvatar, WithContacts, WithMessages, WithNotes, WithSearch, WithSort, WithTasks;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(CustomerRole::class, 'customer_role_id');
    }

    public function scopeWithSearch($query, $search): void
    {
        $this->search($query, $search, [
            'first_name_normalized',
            'last_name_normalized',
            'email',
        ]);
    }

    public function scopeWithSort($query, ?string $sort_by, ?string $sort_direction): void
    {
        $this->sort($query, $sort_by, $sort_direction, [
            'first_name',
            'last_name',
            'email',
        ], 'last_name');
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
