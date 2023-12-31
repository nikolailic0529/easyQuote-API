<?php

namespace App\Domain\QuoteFile\Concerns;

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
        if (is_null($value)) {
            return [];
        }

        return json_decode($value, true) ?? [];
    }

    public function getFormattedMetaAttributesAttribute(): array
    {
        return array_map(function ($attribute) {
            $attribute = is_array($attribute) ? implode(', ', $attribute) : $attribute;

            return filled($attribute) ? $attribute : null;
        }, $this->meta_attributes);
    }
}
