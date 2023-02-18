<?php

namespace App\Domain\Pipeliner\Services\Contracts;

interface ContainsRelatedEntities
{
    public function getRelated(): array;

    public function relatedTo(object ...$entities): static;
}
