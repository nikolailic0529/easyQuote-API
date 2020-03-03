<?php

namespace App\Repositories\Concerns;

use App\Collections\MappedRows;
use App\Models\Quote\BaseQuote;
use Illuminate\Support\Collection;

trait FetchesGroupDescription
{
    protected static function mapGroupDescriptionWithRows(BaseQuote $quote, Collection $rows)
    {
        $groups = Collection::wrap($quote->group_description)->keyBy('name');

        return $rows->groupBy('group_name')->transform(
            fn ($group, $name) => static::unionGroupRowsWithDescription(Collection::wrap($groups->get($name)), $group)
        )
        ->values()
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

        return $group->union([
            'rows'          => $rows,
            'total_count'   => $rows->count(),
            'total_price'   => round((float) $rows->sum('price'), 2)
        ]);
    }
}
