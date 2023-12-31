<?php

namespace App\Domain\Pipeliner\Services\Models;

final class QueueCounts implements \JsonSerializable
{
    public function __construct(
        public readonly int $opportunities,
        public readonly int $companies,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'opportunities' => $this->opportunities,
            'companies' => $this->companies,
        ];
    }
}
