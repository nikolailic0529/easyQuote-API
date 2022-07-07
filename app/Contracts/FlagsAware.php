<?php

namespace App\Contracts;

interface FlagsAware
{
    public function setFlags(int $flags): static;
}