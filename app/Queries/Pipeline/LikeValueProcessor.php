<?php

namespace App\Queries\Pipeline;

class LikeValueProcessor
{
    public static function new(): static
    {
        return new static();
    }

    public function __invoke(mixed $value): array
    {
        return collect($value)
            ->lazy()
            ->filter(static fn(mixed $v): bool => is_scalar($v))
            ->map(static fn(mixed $v): string => (string) $v)
            ->map(static fn(string $v): string => "%$v%")
            ->all();
    }
}