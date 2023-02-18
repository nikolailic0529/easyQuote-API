<?php

namespace App\Foundation\Support\Collection;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

if (!function_exists('prioritize')) {
    /**
     * @template T of Collection
     *
     * @psalm-param  T  $collection
     * @psalm-param  Closure(string|int, mixed): bool  $callback
     *
     * @return T
     */
    function prioritize(Collection $collection, \Closure $callback, bool $preserveKeys = false): Collection
    {
        $items = [];

        foreach ($collection->all() as $key => $item) {
            if (call_user_func($callback, $item, $key)) {
                $items = Arr::prepend($items, $item, $key);
            } else {
                $items[$key] = $item;
            }
        }

        if (!$preserveKeys) {
            $items = array_values($items);
        }

        $class = $collection::class;

        return new $class($items);
    }
}
