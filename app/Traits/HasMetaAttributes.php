<?php

namespace App\Traits;

trait HasMetaAttributes
{
    public function storeMetaAttributes(array $attributes): void
    {
        $this->forceFill(['meta_attributes' => json_encode($attributes)])->save();
    }

    public function flushMetaAttributes(): void
    {
        $this->forceFill(['meta_attributes' => null])->save();
    }

    public function getMetaAttributesAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }
}
