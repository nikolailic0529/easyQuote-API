<?php

namespace App\Domain\DocumentMapping\Collections;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class MappedRows extends Collection
{
    public function setCurrency(?string $symbol): MappedRows
    {
        if ($this->isGrouped()) {
            return $this->each(function (Collection $group) use ($symbol) {
                if ($group->has('total_price')) {
                    $group->put('total_price', $symbol.' '.number_format((float) $group['total_price'], 2));
                }

                return $group->put('rows', static::wrap($group->get('rows'))->setCurrency($symbol));
            });
        }

        return $this->each(function (object $row) use ($symbol) {
            if (isset($row->price) && !isset($row->currency_symbol)) {
                $row->price = $symbol.' '.number_format((float) $row->price, 2);
                $row->currency_symbol = $symbol;
            }
        });
    }

    public function multiplePriceValue(float $coef): MappedRows
    {
        if ($this->isGrouped()) {
            return $this->each(function (Collection $group) use ($coef) {
                $group->put('rows', static::wrap($group->get('rows'))->multiplePriceValue($coef));
            });
        }

        return $this->each(function (object $row) use ($coef) {
            if (isset($row->price)) {
                $row->price *= $coef;
            }
        });
    }

    public function exceptHeaders(array $headers = []): MappedRows
    {
        if (empty($headers)) {
            return $this;
        }

        if ($this->isGrouped()) {
            return $this->each(function (Collection $group) use ($headers) {
                $group->put('rows', $rows = static::wrap($group->get('rows'))->exceptHeaders($headers));

                if ($group->has('headers_count')) {
                    $group->put('headers_count', $this->countHeaders($rows->first()));
                }
            });
        }

        return $this->each(function (object $row) use ($headers) {
            foreach ($headers as $header) {
                unset($row->{$header});
            }
        });
    }

    public function setHeadersCount(): MappedRows
    {
        if (!$this->isGrouped()) {
            return $this;
        }

        $first = static::wrap(data_get($this->first(), 'rows'))->first();

        $headersCount = $this->countHeaders($first);

        return $this->each(function (Collection $group) use ($headersCount) {
            $group->put('headers_count', $headersCount);
        });
    }

    protected function countHeaders($items): int
    {
        return count(Arr::except($this->getArrayableItems($items), ['id', 'replicated_row_id', 'is_selected', 'group_name']));
    }

    protected function isGrouped(): bool
    {
        return Arr::has($this->getArrayableItems($this->first()), 'rows');
    }
}
