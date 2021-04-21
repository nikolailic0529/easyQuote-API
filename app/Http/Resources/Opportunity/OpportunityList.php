<?php

namespace App\Http\Resources\Opportunity;

use App\Models\Opportunity;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\Intl\Currencies;

class OpportunityList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Opportunity|OpportunityList $this */

        static $baseCurrencySymbol;

        if (!isset($baseCurrencySymbol)) {
            $baseCurrencySymbol = Currencies::getSymbol('GBP');
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'opportunity_type' => $this->opportunity_type,
            'account_name' => $this->account_name,
            'account_manager_name' => $this->account_manager_name,
            'opportunity_amount' => sprintf('%s %s', $baseCurrencySymbol, number_format((float)$this->opportunity_amount, 2)),
            'opportunity_start_date' => $this->opportunity_start_date,
            'opportunity_end_date' => $this->opportunity_end_date,
            'opportunity_closing_date' => $this->opportunity_closing_date,
            'sale_action_name' => $this->sale_action_name,
            'project_name' => $this->project_name,
            'status' => $this->status,
            'status_reason' => $this->status_reason,
            'created_at' => $this->created_at
        ];
    }
}
