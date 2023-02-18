<?php

namespace App\Domain\Build\Repositories;

use App\Domain\Build\Contracts\BuildRepositoryInterface;
use App\Domain\Build\Models\Build;

class BuildRepository implements BuildRepositoryInterface
{
    const CACHE_KEY_LAST_BUILD = 'build-last';

    protected \App\Domain\Build\Models\Build $build;

    public function __construct(Build $build)
    {
        $this->build = $build;
    }

    public function all()
    {
        return $this->build->latest()->get();
    }

    public function find(string $id)
    {
        return $this->build->whereId($id)->first();
    }

    public function create(array $attributes): Build
    {
        return tap($this->build->create($attributes), fn (\App\Domain\Build\Models\Build $build) => $this->cacheLastBuild($build));
    }

    public function firstOrCreate(array $attributes, array $values = [])
    {
        return tap($this->build->firstOrCreate($attributes, $values), fn (Build $build) => $this->cacheLastBuild($build));
    }

    public function updateLastOrCreate(array $attributes): Build
    {
        $build = tap($this->last()->fill($attributes))->saveOrFail();

        return tap($build, fn (\App\Domain\Build\Models\Build $updatedBuild) => $this->cacheLastBuild($updatedBuild));
    }

    public function last()
    {
        return cache()->sear(static::CACHE_KEY_LAST_BUILD, fn () => $this->build->latest()->firstOrNew([]));
    }

    private function cacheLastBuild(Build $build)
    {
        cache()->forever(static::CACHE_KEY_LAST_BUILD, $build);
    }
}
