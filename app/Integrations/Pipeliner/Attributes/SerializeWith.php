<?php

namespace App\Integrations\Pipeliner\Attributes;

use App\Integrations\Exceptions\InvalidSerializerClass;
use App\Integrations\Pipeliner\Serializers\Serializer;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SerializeWith
{
    public array $args;

    public function __construct(public string $serializerClass,
                                mixed ...$args)
    {
        if (!is_subclass_of($this->serializerClass, Serializer::class)) {
            throw new InvalidSerializerClass($this->serializerClass);
        }

        $this->args = $args;
    }

    public function serialize(mixed $value): mixed
    {
        /** @var Serializer $serializer */
        $serializer = new $this->serializerClass(...$this->args);

        return $serializer->serialize($value);
    }
}