<?php

namespace App\Domain\DocumentMapping\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportedColumnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'importable_column_id' => $this->importable_column_id,
            'value' => trim($this->value),
            'header' => trim($this->header),
        ];
    }
}
