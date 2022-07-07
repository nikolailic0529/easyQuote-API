<?php

namespace App\Services\Template\Models;

use Illuminate\Support\Arr;

class TemplateControlFilter
{
    protected array $filter = [];

    public static function new(): static
    {
        return new static();
    }

    private function setField(string $key, mixed $value): self
    {
        return tap($this, fn() => $this->filter[$key] = $value);
    }

    public function after(TemplateControlFilter $filter): self
    {
        return tap($this, fn() => $this->filter['after'] = $filter);
    }

    public function name(string $value): self
    {
        return $this->setField(__FUNCTION__, $value);
    }

    public function type(string $value): self
    {
        return $this->setField(__FUNCTION__, $value);
    }

    public function value(string $value): self
    {
        return $this->setField(__FUNCTION__, $value);
    }

    public function label(string $value): self
    {
        return $this->setField(__FUNCTION__, $value);
    }

    public function fieldRequired(string $value): self
    {
        return $this->setField('field_required', $value);
    }

    public function isSatisfied(int $i, array $controls): bool
    {
        if (isset($this->filter['after']) && 0 === $i) {
            return false;
        }

        if (isset($this->filter['after'])) {
            /** @var TemplateControlFilter $after */
            $after = $this->filter['after'];

            return $after->isSatisfied($i - 1, $controls);
        }

        foreach (Arr::except($this->filter, ['after']) as $field => $filterValue) {
            $value = $controls[$i][$field] ?? null;
            $value = is_string($value) ? mb_strtolower(trim($value)) : $value;
            $filterValue = is_string($filterValue) ? mb_strtolower($filterValue) : $filterValue;

            if ($value !== $filterValue) {
                return false;
            }
        }

        return true;
    }
}