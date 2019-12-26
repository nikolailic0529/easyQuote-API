<?php

namespace App\Traits\CachesRelations;

use Illuminate\Database\Eloquent\Model;
use App\Models\CachedRelation\{
    CachedRelationWrapper,
    CachedRelation
};
use Illuminate\Support\Collection;

trait CachesRelations
{
    use BuildsQueries;

    protected static $cacheRelationsEvents = ['creating', 'updating'];

    protected static function bootCachesRelations()
    {
        $relationsToCache = static::getRelationsToCache(new static);

        if ($relationsToCache->isEmpty()) {
            return;
        }

        foreach (static::$cacheRelationsEvents as $event) {
            static::$event(function (Model $model) use ($relationsToCache) {
                $cachedRelations = $model->interceptRelations($relationsToCache);
                $model->fillCachedRelations($cachedRelations);
            });
        }
    }

    public function cacheRelations(): void
    {
        $this->fillCachedRelations($this->getRelationsCache(), true);
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

    public function getCachedRelationsAttribute($value): CachedRelationWrapper
    {
        $relations = json_decode($value, true);

        if (!is_array($relations)) {
            return new CachedRelationWrapper([]);
        }

        return new CachedRelationWrapper($relations);
    }

    /**
     * Filter set relations as we are caching only belongsTo type relations.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Support\Collection
     */
    protected static function getRelationsToCache(Model $model): Collection
    {
        return collect(static::$cacheRelations)->filter(function ($relation) use ($model) {
            return method_exists($model, $relation)
                && $model->{$relation}() instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo;
        });
    }

    protected function interceptRelations(Collection $dirtyRelations): Collection
    {
        $previousCachedRelations = $this->getCachedRelationsAsCollection();

        $dirtyRelations = $dirtyRelations->filter(function ($relation) {
            return isset($this->{$relation})
                && $this->getOriginal($relation . '_id') !== $this->getAttribute($relation . '_id');
        });

        $newlyCachedRelations = $this->getRelationsCache($dirtyRelations);

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

    protected function getRelationsCache(): Collection
    {
        $cacheRelations = func_num_args() > 1
            ? collect(func_get_arg(0))
            : collect(static::$cacheRelations);

        return $cacheRelations->mapWithKeys(function ($relation) {
            $method = method_exists($this->{$relation}, 'toCacheableArray') ? 'toCacheableArray' : 'toArray';

            return [$relation => $this->{$relation}->{$method}()];
        });
    }
}
