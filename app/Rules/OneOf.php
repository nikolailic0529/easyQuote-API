<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationData;

class OneOf implements Rule, DataAwareRule
{
    protected array $data = [];

    protected ?array $distinctValues = null;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(protected mixed  $valueOrCallback,
                                protected bool   $strict = true,
                                protected string $message = 'Only one field can have this value.')
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $callback = is_callable($this->valueOrCallback)
            ? $this->valueOrCallback
            : function (mixed $value): bool {
                if ($this->strict) {
                    return $value === $this->valueOrCallback;
                }

                return $value == $this->valueOrCallback;
            };

        if (!$callback($value)) {
            return true;
        }

        return collect($this->getDistinctValues($attribute))
            ->except($attribute)
            ->doesntContain(static function (mixed $value) use ($callback): bool {
                return $callback($value);
            });
    }

    protected function getDistinctValues(string $attribute): array
    {
        $attributeName = $this->getPrimaryAttribute($attribute);

        $this->distinctValues ??= $this->extractDistinctValues($attributeName);

        if (!array_key_exists($attributeName, $this->distinctValues)) {
            $this->distinctValues[$attributeName] = $this->extractDistinctValues($attributeName);
        }

        return $this->distinctValues[$attributeName];
    }

    /**
     * Extract the distinct values from the data.
     *
     * @param string $attribute
     * @return array
     */
    protected function extractDistinctValues(string $attribute): array
    {
        $attributeData = ValidationData::extractDataFromPath(
            ValidationData::getLeadingExplicitAttributePath($attribute), $this->data
        );

        $pattern = str_replace('\*', '[^.]+', preg_quote($attribute, '#'));

        return Arr::where(Arr::dot($attributeData), function ($value, $key) use ($pattern) {
            return (bool)preg_match('#^'.$pattern.'\z#u', $key);
        });
    }

    protected function getPrimaryAttribute(string $attribute): string
    {
        $parts = Str::of($attribute)->explode('.');

        $parts[$parts->count() - 2] = '*';

        return $parts->join('.');
    }


    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }
}
