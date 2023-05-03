<?php

namespace App\Foundation\Support\Flag\Contracts;

interface FlagsAware
{
    public function setFlags(int $flags): static;
}
