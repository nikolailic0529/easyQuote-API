<?php

namespace App\Domain\Rescue\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class EqCustomer extends JsonResource
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
            'customer_name' => $this->customer_name,
            'rfq_number' => $this->rfq_number,
            'service_levels' => $this->service_levels,
            'invoicing_terms' => $this->invoicing_terms,
            'quotation_valid_until_date' => optional($this->quotation_valid_until_date)->format(config('date.format')),
            'support_start_date' => optional($this->support_start_date)->format(config('date.format')),
            'support_end_date' => optional($this->support_end_date)->format(config('date.format')),
            'created_at' => optional($this->created_at)->format(config('date.format_time')),
        ];
    }
}
