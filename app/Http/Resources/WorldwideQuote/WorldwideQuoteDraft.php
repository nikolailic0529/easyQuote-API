<?php

namespace App\Http\Resources\WorldwideQuote;

use App\Enum\ContractQuoteStage;
use App\Enum\PackQuoteStage;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class WorldwideQuoteDraft extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var WorldwideQuote|WorldwideQuoteDraft $this */

        /** @var User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user_id' => $this->user_id,
            'opportunity_id' => $this->opportunity_id,
            'type_name' => $this->type_name,
            'company_id' => $this->company_id,

            'user_fullname' => $this->user_fullname,
            'company_name' => $this->company_name,
            'customer_name' => $this->customer_name,
            'rfq_number' => $this->rfq_number,
            'valid_until_date' => $this->valid_until_date,
            'customer_support_start_date' => $this->customer_support_start_date,
            'customer_support_end_date' => $this->customer_support_end_date,

            'completeness' => $this->completeness,

            'stage' => with($this->contract_type_id, function (string $contractType) {
                if ($contractType === CT_PACK) {
                    return PackQuoteStage::getLabelOfValue($this->completeness);
                }

                return ContractQuoteStage::getLabelOfValue($this->completeness);

            }),

            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
