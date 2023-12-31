<?php

namespace App\Domain\Worldwide\Resources\V1\Opportunity;

use App\Domain\Worldwide\Enum\OpportunityStatus;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\Intl\Currencies;

class OpportunityAsRelationResource extends JsonResource
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
        /** @var Opportunity|OpportunityList $this */
        static $baseCurrencySymbol;

        if (!isset($baseCurrencySymbol)) {
            $baseCurrencySymbol = Currencies::getSymbol('GBP');
        }

        /** @var \App\Domain\User\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->id,
            'user_id' => $this->owner()->getParentKey(),
            'sales_unit_id' => $this->salesUnit()->getParentKey(),
            'company_id' => $this->company_id,
            'opportunity_type' => $this->opportunity_type,
            'unit_name' => $this->unit_name,
            'status_type' => value(function (): string {
                /** @var \App\Domain\Worldwide\Models\Opportunity|OpportunityList $this */
                if ($this->status === OpportunityStatus::LOST) {
                    return 'Lost';
                }

                if ($this->worldwide_quotes_exists) {
                    return 'Quoted';
                }

                return 'Open';
            }),
            'account_name' => $this->account_name,
            'account_manager_name' => $this->account_manager_name,
            'opportunity_amount' => sprintf('%s %s', $baseCurrencySymbol, number_format((float) $this->opportunity_amount, 2)),
            'opportunity_start_date' => $this->opportunity_start_date,
            'opportunity_end_date' => $this->opportunity_end_date,
            'opportunity_closing_date' => $this->opportunity_closing_date,
            'sale_action_name' => $this->sale_action_name,
            'project_name' => $this->project_name,
            'status' => $this->status,
            'status_reason' => $this->status_reason,

            'quotes_exist' => $this->worldwideQuotes->isNotEmpty(),
            'quote' => QuoteOfOpportunityResource::make($this->worldwideQuotes->first()),

            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->created_at,
            'archived_at' => $this->archived_at,
        ];
    }
}
