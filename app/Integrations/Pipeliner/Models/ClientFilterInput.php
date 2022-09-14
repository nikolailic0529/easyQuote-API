<?php

namespace App\Integrations\Pipeliner\Models;

use JsonSerializable;

class ClientFilterInput extends BaseFilterInput
{
    protected array $fields = [];

    public function firstName(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function middleName(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function lastName(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function email(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function name(EntityFilterStringField $field): static
    {
        return $this->setField(__FUNCTION__, $field);
    }

    public function jsonSerialize(): array
    {
        return array_map(static fn(JsonSerializable $field): array => $field->jsonSerialize(),
            $this->fields);
    }
}