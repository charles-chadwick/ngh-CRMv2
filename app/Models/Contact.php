<?php

namespace App\Models;

use App\Models\Concerns\WithActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes, WithActivity;

    /**
     * Contact type values shown in the type dropdown.
     *
     * @var list<string>
     */
    public const TYPES = ['Office', 'Billing', 'Shipping', 'Mailing', 'Other'];

    protected $fillable = [
        'type',
        'is_primary',
        'street_address',
        'city',
        'state',
        'zip',
        'work_phone',
        'mobile_phone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the parent contactable model (customer, company, etc.).
     */
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }
}
