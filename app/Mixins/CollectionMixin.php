<?php

namespace App\Mixins;

use Illuminate\Support\{
    Str,
    Collection
};

class CollectionMixin
{
    public function exceptEach()
    {
        return function (...$keys) {
            if (!is_iterable((array) head($this->items))) {
                return $this;
            }

            is_iterable(head($keys)) && $keys = head($keys);

            return $this->map(function ($item) use ($keys) {
                return collect($item)->except($keys);
            });
        };
    }

    public function sortKeysByKeys()
    {
        return function (array $keys) {
            return self::transform(function ($row) use ($keys) {
                return array_replace($keys, array_intersect_key((array) $row, $keys));
            });
        };
    }

    public function rowsToGroups()
    {
        return function (string $groupable, ?Collection $meta = null, bool $recalculate = false, ?string $currency = null) {
            $groups = $this->groupBy($groupable)->transform(function ($rows, $key) use ($groupable, $meta, $currency) {
                $meta = isset($meta)
                    ? $meta->firstWhere('name', '==', $key) ?? []
                    : [];
                $rows = collect($rows)
                    ->transform(function ($row) use ($currency) {
                        data_set($row, 'computable_price', data_get($row, 'price', 0.0));
                        data_set($row, 'price', Str::prepend(Str::decimal(data_get($row, 'price', 0.0)), $currency, true));
                        return $row;
                    })
                    ->exceptEach($groupable);

                /**
                 * Count Headers except computable_price
                 */
                $headers_count = $this->wrap($rows->first())->keys()->diff(['computable_price', 'id', 'is_selected'])->count();

                return array_merge((array) $meta, ['headers_count' => $headers_count, $groupable => $key, 'rows' => $rows]);
            })->values();

            filled($meta) && $meta->whereNotIn($groupable, $groups->pluck($groupable))->each(function ($meta) use ($groups) {
                $groups->push(array_merge($meta, ['rows' => collect()]));
            });

            $recalculate && $groups->transform(function ($group) use ($currency) {
                $total_price = Str::decimal($group['rows']->sum('computable_price'));
                data_set($group, 'total_price', Str::prepend($total_price, $currency, true));
                return $group;
            });

            $groups->transform(function ($group) {
                data_set($group, 'rows', $group['rows']->exceptEach('computable_price'));
                return $group;
            });

            return $groups;
        };
    }

    public function sortByFields()
    {
        return function (?array $sortable) {
            if (blank($sortable)) {
                return $this;
            }

            return transform($this, function ($items) use ($sortable) {
                return collect($sortable)->reduce(function ($items, $sort) {
                    $descending = data_get($sort, 'direction') === 'desc' ? true : false;
                    return $items->sortBy(data_get($sort, 'name'), SORT_REGULAR, $descending);
                }, $items)->values();
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

    public function ucfirst()
    {
        return function () {
            return $this->map(function ($value) {
                return ucfirst($value);
            });
        };
    }

    public function eachKeys()
    {
        return function () {
            return $this->map(function ($value) {
                return array_keys($value);
            });
        };
    }

    public function value()
    {
        return function (string $key, $default = null) {
            return data_get($this->first(), $key, $default);
        };
    }
}
