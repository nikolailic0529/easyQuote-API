<?php

namespace App\Domain\UnifiedQuote\Services;

use App\Domain\Rescue\Models\Quote;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as BaseCollection;

class UnifiedQuoteDataMapper
{
    public function mapUnifiedQuotePaginator(LengthAwarePaginatorContract $paginator): LengthAwarePaginatorContract
    {
        $items = $paginator->items();

        $hydrated = [];

        foreach ($items as $item) {
            $hydrated[] = $this->hydrateUnifiedQuoteEntityFromArray((array) $item);
        }

        return new LengthAwarePaginator($hydrated, $paginator->total(), $paginator->perPage(), $paginator->currentPage());
    }

    public function mapUnifiedQuoteCollection(BaseCollection $collection): BaseCollection
    {
        $hydrated = [];

        foreach ($collection as $item) {
            $hydrated[] = $this->hydrateUnifiedQuoteEntityFromArray((array) $item);
        }

        return new BaseCollection($hydrated);
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
