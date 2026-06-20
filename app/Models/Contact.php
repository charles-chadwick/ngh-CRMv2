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

    protected $fillable = [
        'contacable_id',
        'contacable_type',
        'type',
        'street_address',
        'city',
        'state',
        'zip',
        'work_phone',
        'mobile_phone',
    ];

    /**
     * Get the parent-contactable model (customer, company, etc.).
     */
    public function contacable(): MorphTo
    {
        return $this->morphTo();
    }
}
