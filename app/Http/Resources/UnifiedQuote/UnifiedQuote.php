<?php

namespace App\Http\Resources\UnifiedQuote;

use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UnifiedQuote extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Quote|WorldwideQuote|UnifiedQuote $this */

        /** @var User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'business_division' => $this->business_division,
            'contract_type' => $this->contract_type,
            'opportunity_id' => $this->opportunity_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'company_name' => $this->company_name,
            'rfq_number' => $this->rfq_number,
            'completeness' => $this->completeness,

            'sales_order_id' => $this->sales_order_id,
            'has_sales_order' => !is_null($this->sales_order_id),
            'sales_order_submitted' => !is_null($this->sales_order_submitted_at),

            'contract_id' => $this->contract_id,
            'has_contract' => !is_null($this->contract_id),
            'contract_submitted_at' => !is_null($this->contract_submitted_at),

            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],

            'updated_at' => $this->updated_at,
            'activated_at' => $this->activated_at,
            'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOL),
        ];
    }
}
