<?php

namespace App\Integrations\Pipeliner\Serializers;

interface Serializer
{
    public function serialize(mixed $value): mixed;
}