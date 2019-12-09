<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class QuoteRepositoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = QuoteRepositoryResource::collection($this->collection);
        $resource = $this->resource->toArray();

        data_set($resource, 'data', $data);

        return $resource;
    }
}
