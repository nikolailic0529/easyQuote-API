<?php

namespace App\Foundation\Support\Mixins;

use Illuminate\Support\Collection;

/**
 * @mixin Collection
 */
class CollectionMixin
{
    public function exceptEach(): callable
    {
        return function (...$keys) {
            if (!is_iterable((array) head($this->items))) {
                return $this;
            }

            is_iterable(head($keys)) && $keys = head($keys);

            return $this->map(fn ($item) => static::wrap($item)->except($keys));
        };
    }

    public function sortKeysByKeys(): callable
    {
        return function (array $keys) {
            return static::transform(fn ($row) => array_replace($keys, array_intersect_key((array) $row, $keys)));
        };
    }

    public function sortByFields(): callable
    {
        return function ($sortable) {
            if (blank($sortable)) {
                return $this;
            }

            return transform($this, function ($items) use ($sortable) {
                return static::wrap($sortable)
                    ->reduce(
                        fn ($items, $sort) => $items->sortBy(data_get($sort, 'name'), SORT_REGULAR,
                            data_get($sort, 'direction') === 'desc'),
                        $items
                    )
                    ->values();
            });
        };
    }

    public function toString(): callable
    {
        return function (string $key, ?string $additionalKey = null, string $glue = ', ') {
            if (!isset($additionalKey)) {
                return $this->pluck($key)->implode($glue);
            }

            return $this->map(function ($item) use ($key, $additionalKey) {
                $value = data_get($item, $key);
                $additionalValue = data_get($item, $additionalKey);

                return "$value ($additionalValue)";
            })->implode($glue);
        };
    }

    public function implodeWithWrap(): callable
    {
        return function (string $glue, string $wrap) {
            return $wrap.$this->implode($wrap.$glue.$wrap).$wrap;
        };
    }

    public function udiff(): callable
    {
        return function ($items, bool $both = true) {
            return new static(array_udiff($this->items, $this->getArrayableItems($items),
                function ($first, $second) use ($both) {
                    if ($both) {
                        return $first !== $second ? -1 : 0;
                    }

                    return $first <=> $second;
                }));
        };
    }

    public function eachKeys(): callable
    {
        return function () {
            return $this->map(fn ($value) => array_keys($value));
        };
    }
}
