<?php

namespace App\Http\Resources\Opportunity;

use App\Models\Opportunity;
use Illuminate\Http\Resources\Json\JsonResource;

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

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'opportunity_type' => $this->opportunity_type,
            'account_name' => $this->account_name,
            'account_manager_name' => $this->account_manager_name,
            'opportunity_amount' => sprintf('%.2f', (float)$this->opportunity_amount),
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
