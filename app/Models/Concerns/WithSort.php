<?php

namespace App\Models\Concerns;

trait WithSort
{
    abstract public function scopeWithSort($query, ?string $sort_by, ?string $sort_direction);

    /**
     * Apply a whitelisted sort to the query, falling back to a default column.
     *
     * @param  array<int, string>  $fields  Columns that may be sorted on.
     */
    protected function sort($query, ?string $sort_by, ?string $sort_direction, array $fields = [], ?string $default = null)
    {
        $column = in_array($sort_by, $fields, true) ? $sort_by : $default;
        $direction = strtolower((string) $sort_direction) === 'desc' ? 'desc' : 'asc';

        return $query->when($column, fn ($query) => $query->orderBy($column, $direction));
    }
}
