<?php

namespace App\Services\Pipeliner;

final class PipelinerSyncBatch
{
    public ?string $id = null;

    public function hasId(): bool
    {
        return $this->id !== null;
    }
}