<?php

namespace App\Formatters;

interface Formatter
{
    public function __invoke(mixed $value, mixed ...$parameters): mixed;
}