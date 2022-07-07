<?php

namespace App\Contracts;

interface CorrelationAware
{
    public function setCorrelation(string $id): static;
}