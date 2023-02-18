<?php

namespace App\Domain\DocumentMapping\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MappingRow extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $order = 0;

        return [
            'id' => $this->id,
            'columns_data' => Collection::wrap($this->columns_data)->map(function ($column) use (&$order) {
                return (array) $column + ['order' => ++$order];
            })->values(),
        ];
    }
}
