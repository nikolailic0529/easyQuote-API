<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CompanyRepositoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = CompanyRepositoryResource::collection($this->collection);
        $resource = $this->resource->toArray();

        data_set($resource, 'data', $data);

        return $resource;
    }
}
