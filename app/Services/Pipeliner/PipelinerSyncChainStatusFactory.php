<?php

namespace App\Services\Pipeliner;

use Illuminate\Contracts\Cache\Repository;

final class PipelinerSyncChainStatusFactory
{
    public function __construct(
        protected readonly Repository $cache,
    ) {
    }

    public function fromId(string $id): PipelinerSyncChainStatus
    {
        return new PipelinerSyncChainStatus($id, $this->cache);
    }
}