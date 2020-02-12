<?php

namespace App\Http\Resources\ImportableColumn;

use Illuminate\Http\Resources\Json\JsonResource;

class AliasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'alias'      => $this->alias,
        ];
    }
}
