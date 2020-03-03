<?php

namespace App\Http\Resources\ImportedRow;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportedRowResource extends JsonResource
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
            'id'            => $this->id,
            'columns_data'  => $this->columns_data
        ];
    }
}
