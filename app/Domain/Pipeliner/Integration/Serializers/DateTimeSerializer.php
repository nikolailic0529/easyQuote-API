<?php

namespace App\Domain\Pipeliner\Integration\Serializers;

use App\Domain\Pipeliner\Integration\Defaults;

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
