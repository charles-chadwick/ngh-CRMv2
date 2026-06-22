<?php

namespace App\Models;

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Models\Concerns\WithActivity;
use App\Models\Concerns\WithNotes;
use App\Models\Concerns\WithSearch;
use App\Models\Concerns\WithSort;
use App\Models\Concerns\WithTasks;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'company_id',
    'title',
    'type',
    'status',
    'assigned_to',
    'initiated_at',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes, WithActivity, WithNotes, WithSearch, WithSort, WithTasks;

    /**
     * Keep `assigned_at` in step with `assigned_to`: stamp it when a lead is
     * assigned, clear it when the assignment is removed.
     */
    protected static function booted(): void
    {
        static::saving(function (Lead $lead): void {
            if ($lead->isDirty('assigned_to')) {
                $lead->assigned_at = $lead->assigned_to ? now() : null;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'type' => LeadType::class,
            'assigned_at' => 'datetime',
            'initiated_at' => 'datetime',
        ];
    }

    /**
     * Named `assignee` (not `assignedTo`) on purpose: the snake-cased relation
     * key must not collide with the `assigned_to` foreign-key column, or the
     * serialized relation object would overwrite the id the edit form needs.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWithSearch($query, $search): void
    {
        $this->search($query, $search, [
            'title_normalized',
        ]);
    }

    public function scopeWithSort($query, ?string $sort_by, ?string $sort_direction): void
    {
        $this->sort($query, $sort_by, $sort_direction, [
            'title',
            'status',
            'type',
            'created_at',
        ], 'title');
    }
}
