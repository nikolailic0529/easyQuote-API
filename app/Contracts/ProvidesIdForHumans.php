<?php

namespace App\Contracts;

interface ProvidesIdForHumans
{
    public function getIdForHumans(): string;
}