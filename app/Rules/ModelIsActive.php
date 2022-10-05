<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class ModelIsActive implements Rule
{
    public function __construct(
        protected readonly string $modelClass,
        protected string $column = 'activated_at',
        protected string $message = 'The given entity is inactive.',
    ) {
    }

    public static function model(string $modelClass): static
    {
        return new static(modelClass: $modelClass);
    }

    public function setColumn(string $column): static
    {
        return tap($this, fn() => $this->column = $column);
    }

    public function setMessage(string $message): static
    {
        return tap($this, fn() => $this->message = $message);
    }

    public function passes(mixed $attribute, mixed $value): bool
    {
        if (!class_exists($this->modelClass) || !is_a($this->modelClass, Model::class, true)) {
            return false;
        }

        return $this->modelClass::query()
            ->whereKey($value)
            ->whereNotNull($this->column)
            ->exists();
    }

    public function message(): string
    {
        return $this->message;
    }
}
