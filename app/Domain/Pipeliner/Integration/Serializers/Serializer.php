<?php

namespace App\Domain\Pipeliner\Integration\Serializers;

interface Serializer
{
    public function serialize(mixed $value): mixed;
}
