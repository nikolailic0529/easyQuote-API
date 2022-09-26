<?php

namespace App\Foundation\Settings;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Builder;

class DatabaseSettingsStatus
{
    public function __construct(
        protected readonly Builder $schema,
        protected readonly Repository $config,
        protected readonly Cache $cache,
    ) {
    }

    public function isEnabled(): bool
    {
        if ($this->cache->get(static::class)) {
            return true;
        }

        try {
            if (!$this->schema->hasTable($this->config->get('settings.table'))) {
                return false;
            }
        } catch (QueryException $e) {
            return false;
        }

        return $this->cache->forever(static::class, true);
    }

    public function clearCache(): void
    {
        $this->cache->forget(static::class);
    }
}