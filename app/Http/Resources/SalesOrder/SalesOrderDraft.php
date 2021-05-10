<?php

namespace App\Http\Resources\SalesOrder;

use App\Models\SalesOrder;
use App\Services\SalesOrder\SalesOrderNumberHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderDraft extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var SalesOrder|SalesOrderDraft $this */

        return [
            'id' => $this->getKey(),
            'user_id' => $this->user_id,
            'contract_type_id' => $this->contract_type_id,
            'worldwide_quote_id' => $this->worldwide_quote_id,
            'opportunity_id' => $this->opportunity_id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'rfq_number' => $this->rfq_number,
            'order_type' => $this->order_type,
            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at
        ];
    }
}
