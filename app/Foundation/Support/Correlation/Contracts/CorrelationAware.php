<?php

namespace App\Foundation\Support\Correlation\Contracts;

interface CorrelationAware
{
    public function setCorrelation(string $id): static;
}
