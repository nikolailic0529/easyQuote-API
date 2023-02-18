<?php

namespace App\Domain\Worldwide\Resources\V1\SalesOrder;

use App\Domain\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderOfCompany extends JsonResource
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
        /** @var \App\Domain\Worldwide\Models\SalesOrder|SalesOrderSubmitted $this */

        /** @var User|null $user */
        $user = $request->user();

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
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'submitted_at' => $this->submitted_at,
            'is_submitted' => !is_null($this->submitted_at),

            'activated_at' => $this->activated_at,
        ];
    }
}
