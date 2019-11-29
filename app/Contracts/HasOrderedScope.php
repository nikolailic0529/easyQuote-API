<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface HasOrderedScope
{
    /**
     * Default model sorting.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query);
}
