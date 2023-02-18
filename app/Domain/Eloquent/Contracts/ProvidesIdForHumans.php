<?php

namespace App\Domain\Eloquent\Contracts;

interface ProvidesIdForHumans
{
    public function getIdForHumans(): string;
}
