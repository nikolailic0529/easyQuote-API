<?php

namespace App\Domain\Authorization\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class ValidSubmoduleName implements Rule, DataAwareRule
{
    private array $data = [];
    private string $resolvedModule = '';
    private string $resolvedSubmodule = '';

    public function __construct(
        private readonly array $submodules
    ) {
    }

    public function passes(mixed $attribute, mixed $value): bool
    {
        $this->resolvedModule = '';
        $this->resolvedSubmodule = '';

        if (!is_string($attribute)) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        $this->resolvedSubmodule = $value;

        $attribute = Str::of($attribute);

        $moduleInputKey = (string) $attribute->before('.submodules')->append('.module');

        $this->resolvedModule = (string) Arr::get($this->data, $moduleInputKey);

        if (!$this->resolvedModule) {
            return false;
        }

        if (!isset($this->submodules[$this->resolvedModule])) {
            return false;
        }

        if (!isset($this->submodules[$this->resolvedModule][$this->resolvedSubmodule])) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return "Invalid submodule name [$this->resolvedSubmodule] given.";
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }
}
