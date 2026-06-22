<?php

namespace App\Models;

use App\Models\Concerns\WithActivity;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'content',
])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, SoftDeletes, WithActivity;

    /**
     * The parent model this message belongs to (customer, etc.).
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user or customer who wrote this message.
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
