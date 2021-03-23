<?php

namespace App\Contracts;

interface SearchableEntity
{
    public function getKey();

    public function getSearchIndex(): string;

    public function toSearchArray(): array;
}
