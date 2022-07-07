<?php

namespace App\Services\Pipeliner;

use App\Contracts\CauserAware;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;

class SyncPipelinerDataStatus implements CauserAware, \JsonSerializable
{
    protected string $prefix = 'sync_pipeliner_data_status';

    protected ?Model $causer = null;

    public function __construct(protected Cache $cache)
    {
    }

    public function enable(): void
    {
        $this->cache->set($this->getStatusKey(), true);
    }

    public function disable(): void
    {
        $this->cache->forget($this->getStatusKey());
    }

    public function running(): bool
    {
        return (bool)$this->cache->get($this->getStatusKey());
    }

    private function getStatusKey(): string
    {
        return $this->prefix.':'.$this->causer?->getKey();
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }

    public function jsonSerialize(): array
    {
        return ['running' => $this->running()];
    }
}