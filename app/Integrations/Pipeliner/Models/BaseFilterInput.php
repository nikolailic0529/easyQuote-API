<?php

namespace App\Integrations\Pipeliner\Models;

use JsonSerializable;

abstract class BaseFilterInput implements JsonSerializable
{
    protected array $fields = [];

    public static function new(mixed ...$args): static
    {
        return new static(...$args);
    }

    public function jsonSerialize(): array
    {
        return array_map(static fn(JsonSerializable $field): array => $field->jsonSerialize(),
            $this->fields);
    }

    protected function setField(string $name, BaseFilterInput $value): static
    {
        $this->fields[$name] = $value;

        return $this;
    }
}