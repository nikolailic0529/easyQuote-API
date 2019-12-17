<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait CachesRelations
{
    protected static $cacheRelationsEvents = ['creating', 'updating'];

    protected function initializeCachesRelations()
    {
        if (!isset(static::$cacheRelations)) {
            return;
        }

        $this->casts = array_merge($this->casts, ['cached_relations' => 'object']);

        $dirtyRelations = collect(static::$cacheRelations);

        foreach (static::$cacheRelationsEvents as $event) {
            static::$event(function (Model $model) use ($dirtyRelations) {
                $dirtyRelations = $dirtyRelations->filter(function ($relation) use ($model) {
                    return $model->getOriginal($relation . '_id') !== $model->getAttribute($relation . '_id');
                });

                $cached_relations = $dirtyRelations->map(function ($relation) use ($model) {
                    return $model->{$relation}->toArray();
                })->toJson();

                $model->forceFill(compact('cached_relations'));
            });
        }
    }
}
