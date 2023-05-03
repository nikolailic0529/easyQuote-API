<?php

namespace App\Domain\Authorization\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class ValidSubmodulePrivilege implements Rule, DataAwareRule
{
    private array $data = [];
    private string $resolvedModuleName = '';
    private string $resolvedSubmoduleName = '';

    public function __construct(
        private readonly array $submodules
    ) {
    }

    public function passes(mixed $attribute, mixed $value): bool
    {
        $this->resolvedModuleName = '';
        $this->resolvedSubmoduleName = '';

        if (!is_string($attribute)) {
            return false;
        }

        if (!array($value)) {
            return false;
        }

        $attribute = Str::of($attribute);

        $moduleInputKey = (string) $attribute->before('.submodules')->append('.module');
        $submoduleInputKey = (string) $attribute->before('.privilege')->append('.submodule');

        $this->resolvedModuleName = (string) Arr::get($this->data, $moduleInputKey);
        $this->resolvedSubmoduleName = (string) Arr::get($this->data, $submoduleInputKey);

        if (!$this->resolvedModuleName) {
            return false;
        }

        if (!$this->resolvedSubmoduleName) {
            return false;
        }

        if (!$this->submodules) {
            return false;
        }

        if (!isset($this->submodules[$this->resolvedModuleName])) {
            return false;
        }

        if (!isset($this->submodules[$this->resolvedModuleName][$this->resolvedSubmoduleName])) {
            return false;
        }

        $privileges = $this->submodules[$this->resolvedModuleName][$this->resolvedSubmoduleName];

        if (!$privileges) {
            return false;
        }

        if (!isset($privileges[$value])) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return "Invalid privilege for submodule [$this->resolvedSubmoduleName] given.";
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }
}
