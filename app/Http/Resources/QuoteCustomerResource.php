<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteCustomerResource extends JsonResource
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
            'id'            => $this->customer_id,
            'name'          => $this->cached_relations->customer->name,
            'rfq'           => $this->cached_relations->customer->rfq,
            'valid_until'   => $this->cached_relations->customer->valid_until,
            'support_start' => $this->cached_relations->customer->support_start,
            'support_end'   => $this->cached_relations->customer->support_end
        ];
    }
}
