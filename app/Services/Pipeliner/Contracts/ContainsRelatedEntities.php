<?php

namespace App\Services\Pipeliner\Contracts;

interface ContainsRelatedEntities
{
    public function getRelated(): array;

    public function relatedTo(object ...$entities): static;
}