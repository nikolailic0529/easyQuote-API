<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\BuildRepositoryInterface;
use App\Models\System\Build;

class BuildRepository implements BuildRepositoryInterface
{
    const CACHE_KEY_LATEST_BUILD = 'build-latest';

    /** @var \App\Models\System\Build */
    protected Build $build;

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
        return tap($this->build->create($attributes), fn (Build $build) => $this->cacheLatestBuild($build));
    }

    public function firstOrCreate(array $attributes, array $values = [])
    {
        return tap($this->build->firstOrCreate($attributes, $values), fn (Build $build) => $this->cacheLatestBuild($build));
    }

    public function updateLatestOrCreate(array $attributes): Build
    {
        $latestBuild = $this->latest();

        $build = !is_null($latestBuild)
            ? tap($latestBuild)->update($attributes)
            : $this->create($attributes);

        return tap($build, fn (Build $updatedBuild) => $this->cacheLatestBuild($updatedBuild));
    }

    public function latest()
    {
        return cache()->sear(static::CACHE_KEY_LATEST_BUILD, fn () => $this->build->latest()->first());
    }

    private function cacheLatestBuild(Build $build)
    {
        cache()->forever(static::CACHE_KEY_LATEST_BUILD, $build);
    }
}
