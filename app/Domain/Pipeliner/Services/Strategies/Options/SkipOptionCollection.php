<?php

namespace App\Domain\Pipeliner\Services\Strategies\Options;

use Illuminate\Support\Collection;

class SkipOptionCollection extends Collection
{
    public function offsetGet($key): SkipOption
    {
        return parent::offsetGet($key);
    }

    public function implies(string|object $strategy, mixed $entity = null): bool
    {
        return $this->contains(static function (SkipOption $option) use ($strategy, $entity): bool {
            return $option->implies($strategy, $entity);
        });
    }

    public function doesntImply(string|object $strategy, mixed $entity = null): bool
    {
        return !$this->implies($strategy, $entity);
    }
}
