<?php

namespace App\Services\Pipeliner\Models;

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