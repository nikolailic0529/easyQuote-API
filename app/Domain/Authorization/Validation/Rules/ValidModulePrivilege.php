<?php

namespace App\Domain\Authorization\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class ValidModulePrivilege implements Rule, DataAwareRule
{
    private array $data = [];
    private string $resolvedModule = '';

    public function __construct(
        private readonly array $modules
    ) {
    }

    public function passes(mixed $attribute, mixed $value): bool
    {
        if (!is_string($attribute)) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        $moduleInputKey = Str::before($attribute, '.privilege').'.module';
        $this->resolvedModule = Arr::get($this->data, $moduleInputKey);

        if (!$this->resolvedModule) {
            return false;
        }

        if (!isset($this->modules[$this->resolvedModule])) {
            return false;
        }

        $modulePrivileges = $this->modules[$this->resolvedModule];

        if (!$modulePrivileges) {
            return false;
        }

        if (!isset($modulePrivileges[$value])) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return "Invalid privilege for module [$this->resolvedModule] given.";
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }
}
