<?php

namespace App\Domain\Worldwide\Resources\V1\Quote;

use App\Domain\User\Resources\V1\UserRelationResource;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Carbon\CarbonInterval;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Worldwide\Models\WorldwideQuote
 */
class SubmittedWorldwideQuote extends JsonResource
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
        /** @var \App\Domain\User\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),

            'user_id' => $this->user()->getParentKey(),
            'user_fullname' => $this->user_fullname,
            'sharing_users' => UserRelationResource::collection($this->sharingUsers),

            'opportunity_id' => $this->opportunity_id,
            'sales_unit_id' => $this->sales_unit_id,
            'type_name' => $this->type_name,
            'company_id' => $this->company_id,

            'sales_order_id' => $this->sales_order_id,
            'has_sales_order' => !is_null($this->sales_order_id),
            'sales_order_submitted' => !is_null($this->sales_order_submitted_at),

            'has_distributor_files' => $this->contract_type_id === CT_CONTRACT && (bool) $this->has_distributor_files,
            'has_schedule_files' => $this->contract_type_id === CT_CONTRACT && (bool) $this->has_schedule_files,

            'company_name' => $this->company_name,
            'primary_account_id' => $this->primary_account_id,
            'customer_name' => $this->customer_name,
            'end_user_id' => $this->end_user_id,
            'end_user_name' => $this->end_user_name,
            'rfq_number' => $this->rfq_number,
            'valid_until_date' => $this->valid_until_date,
            'customer_support_start_date' => $this->customer_support_start_date,
            'customer_support_end_date' => $this->customer_support_end_date,

            'is_contract_duration_checked' => (bool) $this->is_contract_duration_checked,

            'contract_duration' => value(function (): ?string {
                /** @var WorldwideQuote $this */
                if ($this->is_contract_duration_checked) {
                    return CarbonInterval::months((int) $this->contract_duration_months)->cascade()->forHumans();
                }

                return null;
            }),

            'completeness' => $this->completeness,

            'status' => $this->status,
            'status_reason' => $this->status_reason,

            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'change_status' => $user->can('changeStatus', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
