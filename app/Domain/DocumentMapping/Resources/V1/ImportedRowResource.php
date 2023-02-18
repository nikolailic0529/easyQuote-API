<?php

namespace App\Domain\DocumentMapping\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ImportedRowResource extends JsonResource
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
        return [
            'id' => $this->id,
            'columns_data' => Collection::wrap($this->columns_data)->values(),
        ];
    }
}
