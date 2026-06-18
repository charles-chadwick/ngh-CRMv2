<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Record model changes to the activity log and expose the activity that
 * created the record so the "Created By" attribution can be displayed.
 */
trait WithActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['password', 'remember_token'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The activity recorded when the model was created, along with its causer.
     */
    public function creationActivity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'subject')
            ->where('event', 'created')
            ->with('causer')
            ->oldestOfMany();
    }
}
