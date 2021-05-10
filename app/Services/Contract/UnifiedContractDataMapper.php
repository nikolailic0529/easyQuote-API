<?php

namespace App\Services\Contract;

use App\Models\HpeContract;
use App\Models\Quote\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class UnifiedContractDataMapper
{
    public function mapUnifiedContractPaginator(LengthAwarePaginatorContract $paginator): LengthAwarePaginatorContract
    {
        $items = $paginator->items();

        $hydrated = [];

        foreach ($items as $item) {
            $hydrated[] = $this->hydrateUnifiedContractEntityFromArray((array)$item);
        }

        return new LengthAwarePaginator($hydrated, $paginator->total(), $paginator->perPage(), $paginator->currentPage());
    }

    protected function hydrateUnifiedContractEntityFromArray(array $data): Model
    {
        $documentType = $data['document_type'] ?? null;

        $modelClass = [
                2 => Contract::class,
                3 => HpeContract::class,
            ][$documentType] ?? null;

        if (is_null($modelClass)) {
            throw new \RuntimeException(sprintf("Document Type must be either %s or %s to hydrate model from array.", Q_TYPE_CONTRACT, Q_TYPE_HPE_CONTRACT));
        }

        return (new $modelClass)->newFromBuilder($data);
    }
}
