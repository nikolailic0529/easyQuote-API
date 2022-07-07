<?php

namespace App\Integrations\Pipeliner\Serializers;

use App\Integrations\Pipeliner\Defaults;

class DateTimeSerializer implements Serializer
{
    public function __construct(public readonly string $format = Defaults::DATE_FORMAT)
    {
    }

    public function serialize(mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new \Exception("Value must be a type of \DateTimeInterface.");
        }

        return $value->format($this->format);
    }
}