<?php

namespace App\Domain\VendorServices\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class Warranty extends JsonResource
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
            'warranty_id' => $this->warranty_id,
            'warranty_name' => $this->warranty_name,
            'warranty_type' => $this->warranty_type,
            'warranty_start_date' => optional($this->warranty_start)->format(config('date.format')),
            'warranty_end_date' => optional($this->warranty_end)->format(config('date.format')),
        ];
    }
}
