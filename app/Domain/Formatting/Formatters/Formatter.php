<?php

namespace App\Domain\Formatting\Formatters;

interface Formatter
{
    public function __invoke(mixed $value, mixed ...$parameters): mixed;
}
