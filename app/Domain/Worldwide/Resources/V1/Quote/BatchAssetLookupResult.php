<?php

namespace App\Domain\Worldwide\Resources\V1\Quote;

use Illuminate\Http\Resources\Json\JsonResource;

class BatchAssetLookupResult extends JsonResource
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
        return array_values($this->resource);
    }
}
