<?php

namespace App\Models\Concerns;

trait HasSearch
{
    abstract public function scopeWithSearch($query, $search);
}
