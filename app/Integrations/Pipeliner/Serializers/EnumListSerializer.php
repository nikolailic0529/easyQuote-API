<?php

namespace App\Integrations\Pipeliner\Serializers;

class EnumListSerializer implements Serializer
{
    public function serialize(mixed $value): mixed
    {
        $list = [];

        foreach ($value as $enum) {
            $list[] = $enum instanceof \BackedEnum ? $enum->value : $enum->name;
        }

        return $list;
    }
}