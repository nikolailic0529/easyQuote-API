<?php

namespace App\Domain\Rescue\Concerns;

use App\Domain\DocumentMapping\Collections\MappedRows;
use App\Domain\Rescue\DataTransferObjects\RowsGroup;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\{Contract};
use Illuminate\Support\Collection;

trait FetchesGroupDescription
{
    /** @var \App\Domain\Rescue\Models\BaseQuote|\App\Domain\Rescue\Models\Contract */
    protected static function mapGroupDescriptionWithRows($quote, Collection $rows)
    {
        throw_unless(
            $quote instanceof BaseQuote || $quote instanceof Contract,
            \InvalidArgumentException::class,
            'Unsupported model. Expected either instance of '.BaseQuote::class.' or '.Contract::class
        );

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
            'rows' => $rows->values(),
            'total_count' => $rows->count(),
            'total_price' => round((float) $rows->sum('price'), 2),
        ]);
    }
}
