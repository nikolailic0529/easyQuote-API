<?php

namespace App\Foundation\Validation\Rules;

use App\Foundation\Validation\Rules\Enum\CountOption;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\LazyCollection;

class Count implements Rule
{
    protected LazyCollection $filter;
    protected array $data = [];
    protected ?int $min = null;
    protected ?int $max = null;
    protected ?int $exactly = null;
    protected CountOption $violatedOption = CountOption::NotSet;

    protected string $minMessage = 'The :attribute should contain :limit element or more.|The :attribute should contain :limit elements or more.';
    protected string $maxMessage = 'The :attribute should contain :limit element or less.|The :attribute should contain :limit elements or less.';
    protected string $exactMessage = 'The :attribute should contain exactly :limit element.|The :attribute should contain exactly :limit elements.';

    public function __construct()
    {
        $this->filter = LazyCollection::make(function (): \Generator {
            yield from $this->data;
        });
    }

    public function where($key, $operator = null, $value = null): static
    {
        $args = func_get_args();

        return tap($this, function () use ($args): void {
            $this->filter = $this->filter->where(...$args);
        });
    }

    public function whereStrict($key, $value = null): static
    {
        $args = func_get_args();

        return tap($this, function () use ($args): void {
            $this->filter = $this->filter->whereStrict(...$args);
        });
    }

    public function whereIn($key, $values, $strict = false): static
    {
        $args = func_get_args();

        return tap($this, function () use ($args): void {
            $this->filter = $this->filter->whereIn(...$args);
        });
    }

    public function whereInStrict($key, $values): static
    {
        $args = func_get_args();

        return tap($this, function () use ($args): void {
            $this->filter = $this->filter->whereInStrict(...$args);
        });
    }

    public function min(int $value): static
    {
        return tap($this, fn () => $this->min = $value);
    }

    public function max(int $value): static
    {
        return tap($this, fn () => $this->max = $value);
    }

    public function exactly(int $value): static
    {
        return tap($this, fn () => $this->exactly = $value);
    }

    public function passes($attribute, $value): bool
    {
        if (null === $this->exactly && null === $this->min && null === $this->max) {
            return false;
        }

        if (null !== $this->exactly && null === $this->min && null === $this->max) {
            $this->min = $this->max = $this->exactly;
        }

        $exactlyOptionEnabled = $this->min === $this->max;

        $this->data = $value;

        $count = $this->filter->count();

        if (null !== $this->max && $count > $this->max) {
            $this->violatedOption = $exactlyOptionEnabled ? CountOption::Exactly : CountOption::Max;

            return false;
        }

        if (null !== $this->min && $count < $this->min) {
            $this->violatedOption = $exactlyOptionEnabled ? CountOption::Exactly : CountOption::Min;

            return false;
        }

        return true;
    }

    public function message(): string
    {
        return match ($this->violatedOption) {
            CountOption::NotSet => '',
            CountOption::Exactly => __(trans_choice($this->exactMessage, $this->exactly), ['limit' => $this->exactly]),
            CountOption::Min => __(trans_choice($this->minMessage, $this->min), ['limit' => $this->min]),
            CountOption::Max => __(trans_choice($this->maxMessage, $this->max), ['limit' => $this->max]),
        };
    }

    public function setMinMessage(string $message): static
    {
        return tap($this, fn () => $this->minMessage = $message);
    }

    public function setMaxMessage(string $message): static
    {
        return tap($this, fn () => $this->maxMessage = $message);
    }

    public function setExactMessage(string $message): static
    {
        return tap($this, fn () => $this->exactMessage = $message);
    }
}
