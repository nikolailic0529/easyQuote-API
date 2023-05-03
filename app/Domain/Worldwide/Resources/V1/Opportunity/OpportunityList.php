<?php

namespace App\Domain\Worldwide\Resources\V1\Opportunity;

use App\Domain\Worldwide\Enum\OpportunityStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\Intl\Currencies;

/**
 * @mixin \App\Domain\Worldwide\Models\Opportunity
 */
class OpportunityList extends JsonResource
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
        static $baseCurrencySymbol;

        if (!isset($baseCurrencySymbol)) {
            $baseCurrencySymbol = Currencies::getSymbol('GBP');
        }

        /** @var \App\Domain\User\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),
            'pipeline_id' => $this->pipeline_id,
            'company_id' => $this->company_id,
            'opportunity_type' => $this->opportunity_type,
            'unit_name' => $this->unit_name,
            'status_type' => value(function (): string {
                /** @var \App\Domain\Worldwide\Models\Opportunity|OpportunityList $this */
                if ($this->status === OpportunityStatus::LOST) {
                    return 'Lost';
                }

                if ($this->quotes_exist) {
                    return 'Quoted';
                }

                return 'Open';
            }),
            'primary_account_id' => $this->primaryAccount()->getParentKey(),
            'end_user_id' => $this->endUser()->getParentKey(),
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
            'quotes_exist' => (bool) $this->quotes_exist,
            'quote' => (function (): ?QuoteOfOpportunityResource {
                if ($this->worldwideQuotes->isEmpty()) {
                    return null;
                }

                return QuoteOfOpportunityResource::make($this->worldwideQuotes->first());
            })(),
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
