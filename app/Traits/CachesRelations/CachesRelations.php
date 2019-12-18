<?php

namespace App\Traits\CachesRelations;

use Illuminate\Database\Eloquent\Model;
use App\Models\CachedRelation\{
    CachedRelations,
    CachedRelation
};
use Illuminate\Support\Collection;

trait CachesRelations
{
    use BuildsQueries;

    protected static $cacheRelationsEvents = ['creating', 'updating'];

    protected function initializeCachesRelations()
    {
        if (!isset(static::$cacheRelations)) {
            return;
        }

        $this->hidden = array_merge($this->hidden, ['cached_relations']);

        $dirtyRelations = collect(static::$cacheRelations);

        foreach (static::$cacheRelationsEvents as $event) {
            static::$event(function (Model $model) use ($dirtyRelations) {
                $cachedRelations = $model->interceptRelations($dirtyRelations);
                $model->fillCachedRelations($cachedRelations);
            });
        }
    }

    public function cacheRelations(): void
    {
        $cachedRelations = collect([]);

        foreach (static::$cacheRelations as $relation) {
            if (!isset($this->{$relation})) {
                continue;
            }

            $cachedRelations->put($relation, $this->{$relation}->toArray());
        }

        $this->fillCachedRelations($cachedRelations, true);
    }

    public function clearCachedRelations(): void
    {
        $this->fillCachedRelations(collect([]), true);
    }

    public function getCachedRelationsAsCollection(): Collection
    {
        $cached_relations = $this->attributes['cached_relations'] ?? null;

        $cached_relations = isset($cached_relations) ? json_decode($cached_relations, true) : [];

        return collect($cached_relations);
    }

    public function getCachedRelationsAttribute($value): CachedRelations
    {
        $relations = json_decode($value, true);

        if (!is_array($relations)) {
            return new CachedRelations([]);
        }

        $relations = array_map(function ($relation) {
            return new CachedRelation($relation);
        }, $relations);

        return new CachedRelations($relations);
    }

    protected function interceptRelations(Collection $dirtyRelations): Collection
    {
        $previousCachedRelations = $this->getCachedRelationsAsCollection();

        $dirtyRelations = $dirtyRelations->filter(function ($relation) {
            return isset($this->{$relation})
                && $this->getOriginal($relation . '_id') !== $this->getAttribute($relation . '_id');
        });

        $newlyCachedRelations = $dirtyRelations->mapWithKeys(function ($relation) {
            return [$relation => $this->{$relation}->toArray()];
        });

        return $previousCachedRelations->merge($newlyCachedRelations);
    }

    protected function fillCachedRelations(Collection $cachedRelations, bool $save = false): void
    {
        $this->withoutEvents(function () use ($cachedRelations, $save) {
            $this->forceFill([
                'cached_relations' => $cachedRelations
            ]);

            $save && $this->save();
        });
    }
}
