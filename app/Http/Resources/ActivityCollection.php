<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ActivityCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = ActivityResource::collection($this->collection);

        if ($this->resource instanceof LengthAwarePaginator) {
            $merge = compact('data');

            if (isset($this->additional)) {
                $merge = array_merge($this->additional, $merge);
            }

            return array_merge($this->resource->toArray(), $merge);
        }

        return $data;
    }
}
