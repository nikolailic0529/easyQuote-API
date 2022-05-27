<?php

namespace App\Http\Resources\SalesOrder;

use App\Models\SalesOrder;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderSubmitted extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var SalesOrder|SalesOrderSubmitted $this */

        return [
            'id' => $this->getKey(),
            'user_id' => $this->user_id,
            'contract_type_id' => $this->contract_type_id,
            'worldwide_quote_id' => $this->worldwide_quote_id,
            'opportunity_id' => $this->opportunity_id,
            'order_number' => $this->order_number,
            'order_date' => $this->order_date,
            'status' => $this->status,
            'failure_reason' => $this->failure_reason,
            'status_reason' => $this->status_reason,
            'customer_name' => $this->customer_name,
            'company_name' => $this->company_name,
            'rfq_number' => $this->rfq_number,
            'order_type' => $this->order_type,
            'opportunity_name' => $this->opportunity_name,
            'permissions' => [
                'view' => $request->user()->can('view', $this->resource),
                'update' => $request->user()->can('update', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
                'resubmit' => $request->user()->can('resubmit', $this->resource),
                'refresh_status' => $request->user()->can('refreshStatus', $this->resource),
                'cancel' => $request->user()->can('cancel', $this->resource),
                'export' => $request->user()->can('export', $this->resource),
            ],
            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at
        ];
    }
}
