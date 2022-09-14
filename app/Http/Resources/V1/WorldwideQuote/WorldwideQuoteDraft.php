<?php

namespace App\Http\Resources\V1\WorldwideQuote;

use App\Enum\ContractQuoteStage;
use App\Enum\PackQuoteStage;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use Carbon\CarbonInterval;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorldwideQuote
 */
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
        /** @var User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user_id' => $this->user_id,
            'active_version_id' => $this->active_version_id,
            'opportunity_id' => $this->opportunity_id,
            'sales_unit_id' => $this->sales_unit_id,
            'type_name' => $this->type_name,
            'company_id' => $this->company_id,

            'has_distributor_files' => $this->contract_type_id === CT_CONTRACT && (bool)$this->has_distributor_files,
            'has_schedule_files' => $this->contract_type_id === CT_CONTRACT && (bool)$this->has_schedule_files,

            'user_fullname' => $this->user_fullname,
            'company_name' => $this->company_name,
            'customer_name' => $this->customer_name,
            'end_user_name' => $this->end_user_name,
            'rfq_number' => $this->rfq_number,
            'valid_until_date' => $this->valid_until_date,
            'customer_support_start_date' => $this->customer_support_start_date,
            'customer_support_end_date' => $this->customer_support_end_date,

            'is_contract_duration_checked' => (bool)$this->is_contract_duration_checked,

            'contract_duration' => value(function (): ?string {
                /** @var WorldwideQuote $this */

                if ($this->is_contract_duration_checked) {
                    return CarbonInterval::months((int)$this->contract_duration_months)->cascade()->forHumans();
                }

                return null;
            }),

            'completeness' => $this->completeness,

            'stage' => with($this->contract_type_id, function (string $contractType) {
                if ($contractType === CT_PACK) {
                    return PackQuoteStage::getLabelOfValue($this->completeness);
                }

                return ContractQuoteStage::getLabelOfValue($this->completeness);

            }),

            'has_versions' => $this->whenLoaded('versions', function () {
                /** @var WorldwideQuote $this */
                return $this->versions->count() > 1;
            }),

            'versions' => $this->whenLoaded('versions', function () {
                /** @var WorldwideQuote $this */

                return $this->versions->each(function (WorldwideQuoteVersion $version) {
                    $version->setAttribute('version_name', sprintf('%s %s', $version->user_fullname, $version->user_version_sequence_number));
                    $version->setAttribute('is_active_version', $version->getKey() === $this->active_version_id);
                });
            }),

            'status' => $this->status,
            'status_reason' => $this->status_reason,

            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'change_status' => $user->can('change_status', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
