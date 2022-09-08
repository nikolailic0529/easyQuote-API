<?php

namespace App\Services\Settings\ValueProviders;

interface ValueProvider
{
    public function __invoke(): array;
}