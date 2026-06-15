<?php

namespace App\Models\Concerns;

trait WithSearch
{
    abstract public function scopeWithSearch($query, $search);

    protected function search($query, $search, $fields = []) {
        $term = preg_replace('/[^a-zA-Z0-9]/', '', $search);

        return $query->when($search, function ($query) use ($term, $fields) {
            foreach ($fields as $field) {
                $query->orWhere($field, 'like', "{$term}%");
            }
        });
    }

}
