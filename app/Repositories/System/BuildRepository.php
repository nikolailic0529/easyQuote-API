<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\BuildRepositoryInterface;
use App\Models\System\Build;

class BuildRepository implements BuildRepositoryInterface
{
    const CACHE_KEY_LATEST_BUILD = 'build-latest';

    /** @var \App\Models\System\Build */
    protected $build;

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
        return tap($this->build->create($attributes), function ($build) {
            $this->cacheLatestBuild($build);
        });
    }

    public function firstOrCreate(array $attributes, array $values = [])
    {
        return tap($this->build->firstOrCreate($attributes, $values), function ($build) {
            $this->cacheLatestBuild($build);
        });
    }

    public function latest()
    {
        return cache()->sear(static::CACHE_KEY_LATEST_BUILD, function () {
            return $this->build->latest()->first();
        });
    }

    private function cacheLatestBuild(Build $build)
    {
        cache()->forever(static::CACHE_KEY_LATEST_BUILD, $build);
    }

    private function flushLatestBuildCache(): void
    {
        cache()->forget(static::CACHE_KEY_LATEST_BUILD);
    }
}
