<?php

namespace App\Http\Resources\Lookup;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class Service extends JsonResource
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
            'product_number' => $this->sku,
            'serial_number' => $this->type,
            'model' => $this->model,
            'serial' => $this->serial,
            'description' => $this->description,
            'warranty_start_date' => optional($this->warranty_start_date)->format(config('date.format')),
            'warranty_end_date' => optional($this->warranty_end_date)->format(config('date.format')),
            'warranty_status' => $this->warranty_status,
            'warranties' => Warranty::collection(iterator_to_array($this->warranties)),
        ];
    }
}
