<?php

namespace App\Foundation\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class SchemalessRelation extends Relation
{
    private readonly \Closure $foreignKeyResolver;

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model   $parent
     * @param \Closure(Model): list<int|string>     $foreignKeyResolver
     */
    public function __construct(
        Builder $query,
        Model $parent,
        \Closure $foreignKeyResolver
    ) {
        $this->foreignKeyResolver = $foreignKeyResolver;

        parent::__construct($query, $parent);
    }

    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->query->whereIn($this->related->getQualifiedKeyName(), ($this->foreignKeyResolver)($this->parent));
        }
    }

    public function addEagerConstraints(array $models): void
    {
        $this->query->whereIn($this->related->getQualifiedKeyName(), ($this->foreignKeyResolver)($this->parent));
    }

    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * @param list<Model> $models
     */
    public function match(array $models, Collection $results, $relation): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getKey()] = $result;
        }

        foreach ($models as $model) {
            $matches = [];
            $keys = ($this->foreignKeyResolver)($model);

            foreach ($keys as $k) {
                if (isset($dictionary[$k])) {
                    $matches[] = $dictionary[$k];
                }
            }

            $model->setRelation($relation, $this->related->newCollection($matches));
        }

        return $models;
    }

    public function getResults(): Collection
    {
        $keys = ($this->foreignKeyResolver)($this->parent);

        if (!$keys) {
            return $this->related->newCollection();
        }

        return $this->query->get();
    }
}
