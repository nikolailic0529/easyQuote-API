<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\ValidationLevel;

class ValidationLevelCollection implements \Iterator, \Countable, \JsonSerializable
{
    protected readonly \ArrayIterator $iterator;

    public function __construct(ValidationLevel ...$validationLevels)
    {
        $this->iterator = new \ArrayIterator($validationLevels);
    }

    public static function from(ValidationLevel ...$validationLevels): static
    {
        return new static(...$validationLevels);
    }

    public function jsonSerialize(): array
    {
        $array = [];

        foreach ($this as $item) {
            $array[] = $item->name;
        }

        return $array;
    }

    public function current(): ValidationLevel
    {
        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): mixed
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function count(): int
    {
        return $this->iterator->count();
    }
}
