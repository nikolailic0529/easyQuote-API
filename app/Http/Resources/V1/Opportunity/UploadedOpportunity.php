<?php

namespace App\Http\Resources\V1\Opportunity;

use App\Models\Opportunity;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\Intl\Currencies;

class UploadedOpportunity extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var Opportunity|UploadedOpportunity $this */

        static $baseCurrencySymbol;

        $baseCurrencySymbol ??= Currencies::getSymbol('GBP');

        return [
            'id' => $this->getKey(),
            'company_id' => $this->imported_primary_account_id,
            'contract_type_id' => $this->contract_type_id,
            'opportunity_type' => $this->contractType?->type_short_name,
            'account_name' => $this->importedPrimaryAccount?->company_name,
            'account_manager_name' => $this->accountManager?->user_fullname,
            'opportunity_amount' => (float)$this->base_opportunity_amount,
            'opportunity_amount_formatted' => sprintf('%s %s', $baseCurrencySymbol, number_format((float)$this->base_opportunity_amount, 2)),
            'opportunity_start_date' => $this->opportunity_start_date,
            'opportunity_end_date' => $this->opportunity_end_date,
            'opportunity_closing_date' => $this->opportunity_closing_date,
            'sale_action_name' => $this->sale_action_name,
            'project_name' => $this->project_name,
            'campaign_name' => $this->campaign_name,
            'created_at' => (string)$this->created_at,
        ];
    }
}
