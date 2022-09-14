<?php

namespace App\Http\Resources\V1\SalesOrder;

use App\Models\SalesOrder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SalesOrder
 */
class SalesOrderDraft extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->getKey(),
            'user_id' => $this->user_id,
            'contract_type_id' => $this->contract_type_id,
            'worldwide_quote_id' => $this->worldwide_quote_id,
            'opportunity_id' => $this->opportunity_id,
            'sales_unit_id' => $this->sales_unit_id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'company_name' => $this->company_name,
            'rfq_number' => $this->rfq_number,
            'order_type' => $this->order_type,
            'opportunity_name' => $this->opportunity_name,
            'end_user_name' => $this->end_user_name,
            'account_manager_name' => $this->account_manager_name,
            'account_manager_email' => $this->account_manager_email,
            'assets_count' => $this->assets_count,
            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at
        ];
    }
}
