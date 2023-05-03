<?php

namespace App\Domain\Shared\Eloquent\Contracts;

interface ProvidesIdForHumans
{
    public function getIdForHumans(): string;
}
