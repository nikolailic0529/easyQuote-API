<?php

namespace App\Domain\UnifiedContract\Resources\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserNameCollection extends ResourceCollection
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
        $array = [];

        foreach ($this->resource as $item) {
            $array[] = $item->user_fullname;
        }

        return [
            'data' => $array,
        ];
    }
}
