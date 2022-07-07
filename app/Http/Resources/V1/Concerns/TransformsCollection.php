<?php

namespace App\Http\Resources\V1\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait TransformsCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = $this->resource()::collection($this->collection);

        if (!$this->resource instanceof LengthAwarePaginator) {
            return $data;
        }

        $resource = $this->resource->toArray();
        data_set($resource, 'data', $data);

        return $resource + $this->additional;
    }

    /**
     * Resource class.
     *
     * @return string
     */
    abstract protected function resource(): string;
}
