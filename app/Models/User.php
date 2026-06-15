<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enum\UserRole;
use App\Models\Concerns\WithAvatar;
use App\Models\Concerns\WithSearch;
use Database\Factories\UserFactory;
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
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'role',
    'first_name',
    'last_name',
    'email',
    'password',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable, WithAvatar, WithSearch;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts() : array
    {
        return [
            'role' => UserRole::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = [
        'full_name',
        'initials'
    ];

    public function lastLogin() : BelongsTo
    {
        return $this->belongsTo(Login::class);
    }

    public function scopeWithLastLogin($query)
    {
        return $query->addSelect([
            'last_login_id' => Login::select('id')
                ->whereColumn('user_id', 'users.id')
                ->latest()
                ->take(1),
        ])
            ->with('lastLogin');
    }

    public function scopeWithSearch($query, $search) : void
    {
        $this->search($query, $search, [
            'first_name_normalized',
            'last_name_normalized',
            'email',
            'role'
        ]);
    }

    public function registerMediaCollections() : void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp'
            ]);
    }

    public function registerMediaConversions(?Media $media = null) : void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 192, 192)
            ->nonQueued();
    }

    public function getFullNameAttribute() : string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute() : string
    {
        return $this->first_name[0].$this->last_name[0];
    }
}
