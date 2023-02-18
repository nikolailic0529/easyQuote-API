<?php

namespace App\Domain\Pipeliner\Services\Models;

final class QueueSyncResult implements \JsonSerializable
{
    public function __construct(public readonly bool $queued)
    {
    }

    public function jsonSerialize(): array
    {
        return ['queued' => $this->queued];
    }
}
