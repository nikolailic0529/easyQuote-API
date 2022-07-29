<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\EntityFilterStringOperator;

class EntityFilterStringField extends BaseFilterInput
{
    private function __construct(public readonly EntityFilterStringOperator $op,
                                 public readonly mixed $value)
    {
    }

    public static function contains(string $value, string ...$values): static
    {
        return new static(EntityFilterStringOperator::contains, [$value, ...$values]);
    }

    public static function empty(bool $value = true): static
    {
        return new static(EntityFilterStringOperator::empty, $value);
    }

    public static function ends(string $value, string ...$values): static
    {
        return new static(EntityFilterStringOperator::ends, [$value, ...$values]);
    }

    public static function eq(string $value, string ...$values): static
    {
        return new static(EntityFilterStringOperator::eq, [$value, ...$values]);
    }

    public static function icontains(string $value, string ...$values): static
    {
        return new static(EntityFilterStringOperator::icontains, [$value, ...$values]);
    }

    public static function iends(string $value, string ...$values): static
    {
        return new static(EntityFilterStringOperator::iends, [$value, ...$values]);
    }

    public static function ieq(string $value, string ...$values): static
    {
        return new static(EntityFilterStringOperator::ieq, [$value, ...$values]);
    }

    public static function istarts(string $value, string ...$values): static
    {
        return new static(EntityFilterStringOperator::istarts, [$value, ...$values]);
    }

    public static function null(bool $value = true): static
    {
        return new static(EntityFilterStringOperator::null, $value);
    }

    public static function starts(string $value, string ...$values): static
    {
        return new static(EntityFilterStringOperator::starts, [$value, ...$values]);
    }

    public function jsonSerialize(): array
    {
        return [$this->op->name => $this->value];
    }
}