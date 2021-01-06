<?php

namespace App\Builders;

use App\Models\HpeContract;
use App\Models\Quote\Contract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UnifiedContractBuilder extends Builder
{

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Model[]|static[]
     */
    public function getModels($columns = ['*'])
    {
        return $this->hydrate(
            $this->query->get($columns)->all()
        )->all();
    }

    /**
     * Create a collection of models from plain arrays.
     *
     * @param  array  $items
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hydrate(array $items)
    {
        $contractInstance = new Contract;
        $hpeContractInstance = new HpeContract;

        return new Collection(array_map(function ($item) use ($contractInstance, $hpeContractInstance) {
            if (data_get($item, 'document_type') === 3) {
                return $hpeContractInstance->newFromBuilder($item);
            }

            return $contractInstance->newFromBuilder($item);
        }, $items));
    }
}
