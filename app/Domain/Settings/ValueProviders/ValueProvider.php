<?php

namespace App\Domain\Settings\ValueProviders;

interface ValueProvider
{
    public function __invoke(): array;
}
