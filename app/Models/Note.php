<?php

namespace App\Models;

use App\Models\Concerns\WithActivity;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type',
    'title',
    'content',
])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, SoftDeletes, WithActivity;

    /**
     * The note types that may be assigned.
     *
     * @var list<string>
     */
    public const TYPES = ['General', 'Call', 'Meeting', 'Email', 'Follow Up'];

    /**
     * Get the parent notable model (customer, company, etc.).
     */
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
