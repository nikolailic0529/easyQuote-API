<?php

namespace App\Mixins;

use Illuminate\Support\{
    Arr,
    Str,
    Collection
};

/**
 * @mixin Collection
 */
class CollectionMixin
{
    public function prioritize()
    {
        return function (callable $callable): Collection {
            $items = [];

            foreach ($this->items as $key => $item) {
                if (call_user_func($callable, $item, $key)) {
                    $items = Arr::prepend($items, $item, $key);
                } else {
                    $items[$key] = $item;
                }
            }

            return new static($items);
        };
    }

    public function exceptEach()
    {
        return function (...$keys) {
            if (!is_iterable((array) head($this->items))) {
                return $this;
            }

            is_iterable(head($keys)) && $keys = head($keys);

            return $this->map(fn ($item) => static::wrap($item)->except($keys));
        };
    }

    public function sortKeysByKeys()
    {
        return function (array $keys) {
            return static::transform(fn ($row) => array_replace($keys, array_intersect_key((array) $row, $keys)));
        };
    }

    public function sortByFields()
    {
        return function ($sortable) {
            if (blank($sortable)) {
                return $this;
            }

            return transform($this, function ($items) use ($sortable) {
                return static::wrap($sortable)
                    ->reduce(
                        fn ($items, $sort) => $items->sortBy(data_get($sort, 'name'), SORT_REGULAR, data_get($sort, 'direction') === 'desc'),
                        $items
                    )
                    ->values();
            });
        };
    }

    public function toString()
    {
        return function (string $key, ?string $additionalKey = null, string $glue = ', ') {
            if (!isset($additionalKey)) {
                return $this->pluck($key)->implode($glue);
            }

            return $this->map(function ($item) use ($key, $additionalKey) {
                $value = data_get($item, $key);
                $additionalValue = data_get($item, $additionalKey);

                return "{$value} ($additionalValue)";
            })->implode($glue);
        };
    }

    public function implodeWithWrap()
    {
        return function (string $glue, string $wrap) {
            return $wrap . $this->implode($wrap . $glue . $wrap) . $wrap;
        };
    }

    public function udiff()
    {
        return function ($items, bool $both = true) {
            return new static(array_udiff($this->items, $this->getArrayableItems($items), function ($first, $second) use ($both) {
                if ($both) {
                    return $first !== $second ? -1 : 0;
                }

                return $first <=> $second;
            }));
        };
    }

    public function eachKeys()
    {
        return function () {
            return $this->map(fn ($value) => array_keys($value));
        };
    }
}
