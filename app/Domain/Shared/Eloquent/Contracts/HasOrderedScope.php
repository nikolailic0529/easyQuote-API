<?php

namespace App\Domain\Shared\Eloquent\Contracts;

interface HasOrderedScope
{
    /**
     * Default model sorting.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query);
}
