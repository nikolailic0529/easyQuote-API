<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\EntityFilterStringOperator;

class EntityFilterStringField extends BaseFilterInput
{
    private function __construct(public readonly EntityFilterStringOperator $op,
                                 public readonly mixed $value)
    {
    }

    public static function contains(string $value): static
    {
        return new static(EntityFilterStringOperator::contains, $value);
    }

    public static function empty(bool $value = true): static
    {
        return new static(EntityFilterStringOperator::empty, $value);
    }

    public static function ends(string $value): static
    {
        return new static(EntityFilterStringOperator::ends, $value);
    }

    public static function eq(string $value): static
    {
        return new static(EntityFilterStringOperator::eq, $value);
    }

    public static function icontains(string $value): static
    {
        return new static(EntityFilterStringOperator::icontains, $value);
    }

    public static function iends(string $value): static
    {
        return new static(EntityFilterStringOperator::iends, $value);
    }

    public static function ieq(string $value): static
    {
        return new static(EntityFilterStringOperator::ieq, $value);
    }

    public static function istarts(string $value): static
    {
        return new static(EntityFilterStringOperator::istarts, $value);
    }

    public static function null(bool $value = true): static
    {
        return new static(EntityFilterStringOperator::null, $value);
    }

    public static function starts(string $value): static
    {
        return new static(EntityFilterStringOperator::starts, $value);
    }

    public function jsonSerialize(): array
    {
        return [$this->op->name => $this->value];
    }
}