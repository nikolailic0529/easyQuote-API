<?php

namespace App\Http\Resources\V1\Discount;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DiscountListCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
