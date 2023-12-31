<?php

namespace App\Domain\Pipeliner\Services;

final class PipelinerSyncAggregate
{
    public ?string $id = null;

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function withId(string $id): PipelinerSyncAggregate
    {
        return tap($this, fn () => $this->id = $id);
    }
}
