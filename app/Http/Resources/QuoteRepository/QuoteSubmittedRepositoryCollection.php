<?php

namespace App\Http\Resources\QuoteRepository;

use Illuminate\Http\Resources\Json\ResourceCollection;

class QuoteSubmittedRepositoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = QuoteSubmittedRepositoryResource::collection($this->collection);

        $resource = $this->resource->toArray();

        data_set($resource, 'data', $data);

        return $resource;
    }
}