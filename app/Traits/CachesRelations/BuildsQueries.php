<?php

namespace App\Traits\CachesRelations;

use Illuminate\Database\Eloquent\Builder;
use Str;

trait BuildsQueries
{
    public function scopeOrderByCachedRelation(Builder $query, string $relation, string $direction = 'asc'): Builder
    {
        if (!Str::contains($relation, '.')) {
            return $query;
        }

        $attribute = str_replace('.', '->', $relation);

        return $query->orderBy('cached_relations->' . $attribute, $direction);
    }

    public function scopeWithCacheableRelations(Builder $query): Builder
    {
        return $query->with(static::$cacheRelations ?? []);
    }
}
