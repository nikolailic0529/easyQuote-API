<?php

namespace App\Http\Resources\UnifiedContract;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerNameCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $array = [];

        foreach ($this->resource as $item) {
            $array[] = $item->customer_name;
        }

        return [
            'data' => $array
        ];
    }
}
