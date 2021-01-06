<?php

namespace App\Traits\CachesRelations;

use Illuminate\Database\Eloquent\Model;
use App\Models\CachedRelation\{
    CachedRelationWrapper,
};
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        $relations = json_decode($value, true) ?? [];

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
        return collect(static::$cacheRelations ?? [])->filter(
            fn ($relation) =>
            method_exists($model, $relation)
                && $model->{$relation}() instanceof BelongsTo
        );
    }

    protected function interceptRelations(Collection $dirtyRelations): Collection
    {
        $previousCachedRelations = $this->getCachedRelationsAsCollection();

        $dirtyRelations = $dirtyRelations->filter(
            fn ($relation) =>
            isset($this->{$relation})
                && $this->getOriginal($this->{$relation}()->getForeignKeyName()) !== $this->getAttribute($this->{$relation}()->getForeignKeyName())
        );

        return $previousCachedRelations->merge($this->getRelationsCache($dirtyRelations));
    }

    protected function fillCachedRelations(Collection $cachedRelations, bool $save = false): void
    {
        $this->forceFill([
            'cached_relations' => $cachedRelations
        ]);

        if ($save) {
            $this->setKeysForSaveQuery($this->newModelQuery())->toBase()
                ->update(['cached_relations' => $cachedRelations->toJson()]);

            $this->syncOriginalAttribute('cached_relations');
        }
    }

    protected function getRelationsCache(): Collection
    {
        $cacheRelations = func_num_args() > 1
            ? collect(func_get_arg(0))
            : collect(static::$cacheRelations ?? []);

        return $cacheRelations
            /**
             * If attribute exists as a method of the model, we will ensure that it is the eloquent relationship.
             */
            ->filter(fn ($relation) => method_exists($this, $relation) && is_a($this->{$relation}(), Relation::class))
            /**
             * Then we will load the relationship and ensure that result exists and it is an instance of the eloquent model.
             */
            ->filter(fn ($relation) => $this->{$relation} instanceof Model)
            ->mapWithKeys(function ($relation) {
                $method = method_exists($this->{$relation}, 'toCacheableArray') ? 'toCacheableArray' : 'toArray';

                return [$relation => $this->{$relation}->{$method}()];
            });
    }
}
