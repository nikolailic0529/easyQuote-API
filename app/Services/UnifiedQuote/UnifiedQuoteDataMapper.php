<?php

namespace App\Services\UnifiedQuote;

use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class UnifiedQuoteDataMapper
{
    public function mapUnifiedQuotePaginator(LengthAwarePaginatorContract $paginator): LengthAwarePaginatorContract
    {
        $items = $paginator->items();

        $hydrated = [];

        foreach ($items as $item) {
            $hydrated[] = $this->hydrateUnifiedQuoteEntityFromArray((array)$item);
        }

        return new LengthAwarePaginator($hydrated, $paginator->total(), $paginator->perPage(), $paginator->currentPage());
    }

    protected function hydrateUnifiedQuoteEntityFromArray(array $data): Model
    {
        $businessDivision = $data['business_division'] ?? null;

        if ($businessDivision === 'Worldwide') {
            return (new WorldwideQuote())->newFromBuilder($data);
        }

        if ($businessDivision === 'Rescue') {
            return (new Quote())->newFromBuilder($data);
        }

        throw new \RuntimeException("Business Division must be either 'Rescue' or 'Worldwide' to hydrate model from array.");
    }
}
