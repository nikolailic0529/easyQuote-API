<?php

namespace App\Domain\Task\Resources\V1;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
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

    protected function resource(): string
    {
        return TaskListResource::class;
    }
}
