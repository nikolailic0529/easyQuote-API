<?php

namespace App\Collections;

use Illuminate\Support\Collection;
use Arr, Str;

class MappedRows extends Collection
{
    public function setCurrency(?string $symbol)
    {
        if ($this->isGrouped()) {
            return $this->map(function (Collection $group) use ($symbol) {
                $group->put('total_price', Str::prepend(Str::decimal($group->get('total_price'), 2), $symbol));

                $rows = $group->getRows();
                $rows instanceof static ? $rows : static::make($rows);
                $rows = $rows->setCurrency($symbol);

                return $group->put('rows', $rows);
            });
        }

        return $this->map(function ($row) use ($symbol) {
            $price = Str::prepend(Str::decimal(data_get($row, 'price'), 2), $symbol);
            return data_set($row, 'price', $price);
        });
    }

    public function sortKeysBy(iterable $keys)
    {
        if ($this->isGrouped()) {
            return $this->map(fn (Collection $group) => $group->put('rows', $group->getRows()->sortKeysBy($keys)));
        }

        $keys = array_flip($this->getArrayableItems($keys));

        return $this->map(fn ($row) => array_replace($keys, array_intersect_key($this->getArrayableItems($row), $keys)));
    }

    public function setHeadersCount()
    {
        if (!$this->isGrouped()) {
            return $this;
        }

        $first = static::wrap(data_get($this->first(), 'rows'))->first();

        $headersCount = $this->countHeaders($first);

        return $this->map(fn ($group) => data_set($group, 'headers_count', $headersCount));
    }

    public function exceptHeaders(array $headers = [])
    {
        if (blank($headers)) {
            return $this;
        }

        if ($this->isGrouped()) {
            return $this->map(function (Collection $group) use ($headers) {
                $rows = $group->getRows()->exceptHeaders($headers);

                $group->put('rows', $rows);

                if ($group->has('headers_count')) {
                    $group->put('headers_count', $this->countHeaders($rows->first()));
                }

                return $group;
            });
        }

        return $this->map(fn ($row) => Arr::except((array) $row, $headers));
    }

    protected function getRows()
    {
        return static::wrap($this->get('rows'));
    }

    protected function countHeaders($items): int
    {
        return count(Arr::except($this->getArrayableItems($items), ['id', 'is_selected', 'group_name']));
    }

    protected function isGrouped(): bool
    {
        return Arr::has($this->getArrayableItems($this->first()), 'rows');
    }
}
