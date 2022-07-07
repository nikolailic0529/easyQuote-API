<?php

namespace App\Integrations\Pipeliner\Models;

class BaseInputCollection implements \Iterator, \Countable, \JsonSerializable
{
    protected readonly \ArrayIterator $iterator;

    public function __construct(BaseInput ...$collection)
    {
        $this->iterator = new \ArrayIterator($collection);
    }

    public function current(): mixed
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

    public function jsonSerialize(): array
    {
        $collection = $this->iterator->getArrayCopy();

        $array = [];

        foreach ($collection as $key => $item) {

            if (!$item instanceof BaseInput) {
                continue;
            }

            $array[$key] = $item->jsonSerialize();

        }

        return $array;
    }
}