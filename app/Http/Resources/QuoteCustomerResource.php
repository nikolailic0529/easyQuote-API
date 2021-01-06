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
        $customerName = $this->document_type === Q_TYPE_HPE_CONTRACT ? $this->hpe_contract_customer_name : $this->customer->name;

        return [
            'id'            => $this->customer_id,
            'name'          => $customerName,
            'rfq'           => $this->customer->rfq,
            'valid_until'   => $this->customer->valid_until,
            'support_start' => $this->customer->support_start,
            'support_end'   => $this->customer->support_end
        ];
    }
}
