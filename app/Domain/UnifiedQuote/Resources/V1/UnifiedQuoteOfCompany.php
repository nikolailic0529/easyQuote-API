<?php

namespace App\Domain\UnifiedQuote\Resources\V1;

use App\Domain\Rescue\Models\Quote;
use App\Domain\Worldwide\Enum\QuoteStatus;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorldwideQuote|Quote
 */
class UnifiedQuoteOfCompany extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     */
    public function toArray($request): array
    {
        /** @var Quote|WorldwideQuote|UnifiedQuote $this */

        /** @var \App\Domain\User\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user_id' => $this->user_id,
            'business_division' => $this->business_division,
            'contract_type' => $this->contract_type,
            'opportunity_id' => $this->opportunity_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'end_user_id' => $this->end_user_id,
            'end_user_name' => $this->end_user_name,
            'company_name' => $this->company_name,
            'rfq_number' => $this->rfq_number,
            'completeness' => $this->completeness,

            'sales_order_id' => $this->sales_order_id,
            'has_sales_order' => !is_null($this->sales_order_id),
            'sales_order_submitted' => !is_null($this->sales_order_submitted_at),

            'contract_id' => $this->contract_id,
            'has_contract' => !is_null($this->contract_id),
            'contract_submitted_at' => !is_null($this->contract_submitted_at),

            'active_version_id' => $this->active_version_id,

            'has_distributor_files' => value(function (): bool {
                if ($this->resource instanceof WorldwideQuote) {
                    return (bool) $this->has_distributor_files;
                }

                if (!is_null($this->active_version_id)) {
                    return !is_null($this->active_version_distributor_file_id);
                }

                return !is_null($this->distributor_file_id);
            }),

            'has_schedule_files' => value(function (): bool {
                if ($this->resource instanceof WorldwideQuote) {
                    return (bool) $this->has_schedule_files;
                }

                if (!is_null($this->active_version_id)) {
                    return !is_null($this->active_version_schedule_file_id);
                }

                return !is_null($this->schedule_file_id);
            }),

            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],

            'status_type' => match ($this->status) {
                QuoteStatus::DEAD => 'Dead',
                QuoteStatus::ALIVE => 'Alive',
            },

            'submission_status' => match (!is_null($this->submitted_at)) {
                true => 'Submitted',
                false => 'Drafted',
            },

            'submitted_at' => $this->submitted_at,
            'is_submitted' => !is_null($this->submitted_at),

            'updated_at' => $this->updated_at,
            'activated_at' => $this->activated_at,
            'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOL),
        ];
    }
}
