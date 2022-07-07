<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Attributes\SerializeWith;
use App\Integrations\Pipeliner\Defaults;
use App\Integrations\Pipeliner\Enum\InputValueEnum;

abstract class BaseInput implements \JsonSerializable
{
    public function getModifiedFields(): array
    {
        $fields = [];

        foreach ($this->getProperties() as $property) {
            $name = $property->getName();
            $value = $this->{$name};

            if ('id' === $name || InputValueEnum::Miss === $value) {
                continue;
            }

            $fields[$name] = $value;
        }

        return $fields;
    }

    public function jsonSerialize(): array
    {
        $array = [];

        foreach ($this->getProperties() as $property) {
            $value = $property->getValue($this);

            if (InputValueEnum::Miss === $value) {
                continue;
            }

            $array[$property->getName()] = $this->serializeProperty($property);
        }

        return $array;
    }

    protected function serializeProperty(\ReflectionProperty $property): mixed
    {
        $attrs = $property->getAttributes();
        $value = $property->getValue($this);

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof \UnitEnum) {
            $value = $value->name;
        }

        foreach ($attrs as $attr) {
            if (SerializeWith::class === $attr->getName()) {
                /** @var SerializeWith $serializeWith */
                $serializeWith = $attr->newInstance();

                $value = $serializeWith->serialize($value);
            }
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format(Defaults::DATE_FORMAT);
        }

        if ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        return $value;
    }

    /**
     * @return \ReflectionProperty[]
     */
    protected function getProperties(): array
    {
        $reflection = new \ReflectionClass(static::class);

        $properties = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $properties[] = $property;
        }

        return $properties;
    }
}