<?php

namespace App\Repositories\Concerns;

use App\Collections\MappedRows;
use App\DTO\RowsGroup;
use App\Models\Quote\BaseQuote;
use Illuminate\Support\Collection;

trait FetchesGroupDescription
{
    protected static function mapGroupDescriptionWithRows(BaseQuote $quote, Collection $rows)
    {
        return MappedRows::wrap($quote->group_description)->map(
            fn (RowsGroup $group) => static::unionGroupRowsWithDescription($group->toArray(), $rows->whereIn('id', $group->rows_ids))
        )
            ->sortByFields($quote->sort_group_description);
    }

    private static function fetchRowsSearchInput(string $query): array
    {
        return (array) array_filter(array_map('trim', explode(',', $query)));
    }

    private static function unionGroupRowsWithDescription(iterable $group, iterable $rows): Collection
    {
        $group = MappedRows::make($group);
        $rows = MappedRows::make($rows);

        return $group->merge([
            'rows'          => $rows->values(),
            'total_count'   => $rows->count(),
            'total_price'   => round((float) $rows->sum('price'), 2)
        ]);
    }
}
