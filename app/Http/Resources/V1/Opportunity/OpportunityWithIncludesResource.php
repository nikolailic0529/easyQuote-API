<?php

namespace App\Http\Resources\V1\Opportunity;


use App\Http\Resources\V1\User\UserRelationResource;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\Intl\Currencies;

class OpportunityWithIncludesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)

        /** @var Opportunity|OpportunityWithIncludesResource $this */
    {
        return [
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),
            'user' => UserRelationResource::make($this->owner),

            'sales_unit_id' => $this->salesUnit()->getParentKey(),
            'sales_unit' => $this->salesUnit,

            'pipeline_id' => $this->pipeline()->getParentKey(),
            'pipeline' => $this->pipeline,

            'pipeline_stage_id' => $this->pipelineStage()->getParentKey(),
            'pipeline_stage' => $this->pipelineStage,

            'contract_type_id' => $this->contractType()->getParentKey(),
            'contract_type' => $this->contractType,

            'primary_account_id' => $this->primaryAccount()->getParentKey(),
            'primary_account' => transform($this->primaryAccount, static function (Company $primaryAccount): Company {
                $primaryAccount->loadMissing(['addresses.country', 'contacts']);

                $primaryAccount->addresses->each(function (Address $address): void {
                    $address->setAttribute('is_default', (bool)$address->pivot->is_default);
                });

                $primaryAccount->contacts->each(function (Contact $contact): void {
                    $contact->setAttribute('is_default', (bool)$contact->pivot->is_default);
                });

                return $primaryAccount->setAttribute('vendor_ids', $primaryAccount->vendors->modelKeys());
            }),

            'primary_account_contact_id' => $this->primaryAccountContact()->getParentKey(),
            'primary_account_contact' => $this->primaryAccountContact,

            'end_user_id' => $this->endUser()->getParentKey(),
            'end_user' => transform($this->endUser, static function (Company $endUser): Company {
                $endUser->loadMissing(['addresses.country', 'contacts']);

                $endUser->addresses->each(function (Address $address) {
                    $address->setAttribute('is_default', (bool)$address->pivot->is_default);
                });

                $endUser->contacts->each(function (Contact $contact) {
                    $contact->setAttribute('is_default', (bool)$contact->pivot->is_default);
                });

                return $endUser->setAttribute('vendor_ids', $endUser->vendors->modelKeys());
            }),

            'recurrence' => OpportunityRecurrenceResource::make($this->recurrence),

            'are_end_user_addresses_available' => (bool)$this->are_end_user_addresses_available,
            'are_end_user_contacts_available' => (bool)$this->are_end_user_contacts_available,

            'account_manager_id' => $this->accountManager()->getParentKey(),
            'account_manager' => $this->accountManager,

            'project_name' => $this->project_name,
            'nature_of_service' => $this->nature_of_service,
            'renewal_month' => $this->renewal_month,
            'renewal_year' => $this->renewal_year,
            'customer_status' => $this->customer_status,
            'end_user_name' => $this->end_user_name,
            'hardware_status' => $this->hardware_status,
            'region_name' => $this->region_name,
            'opportunity_start_date' => $this->opportunity_start_date,
            'is_opportunity_start_date_assumed' => (bool)$this->is_opportunity_start_date_assumed,
            'opportunity_end_date' => $this->opportunity_end_date,
            'is_opportunity_end_date_assumed' => (bool)$this->is_opportunity_end_date_assumed,
            'opportunity_closing_date' => $this->opportunity_closing_date,
            'contract_duration_months' => $this->contract_duration_months,
            'is_contract_duration_checked' => (bool)$this->is_contract_duration_checked,
            'expected_order_date' => $this->expected_order_date,
            'customer_order_date' => $this->customer_order_date,
            'purchase_order_date' => $this->purchase_order_date,
            'supplier_order_date' => $this->supplier_order_date,
            'supplier_order_transaction_date' => $this->supplier_order_transaction_date,
            'supplier_order_confirmation_date' => $this->supplier_order_confirmation_date,
            'opportunity_amount' => $this->opportunity_amount,
            'base_opportunity_amount' => transform($this->base_opportunity_amount, self::formatBasePriceValue(...)),
            'opportunity_amount_currency_code' => $this->opportunity_amount_currency_code,
            'purchase_price' => $this->purchase_price,
            'base_purchase_price' => transform($this->base_purchase_price, self::formatBasePriceValue(...)),
            'purchase_price_currency_code' => $this->purchase_price_currency_code,
            'list_price' => $this->list_price,
            'base_list_price' => transform($this->base_list_price, self::formatBasePriceValue(...)),
            'list_price_currency_code' => $this->list_price_currency_code,
            'estimated_upsell_amount' => $this->estimated_upsell_amount,
            'estimated_upsell_amount_currency_code' => $this->estimated_upsell_amount_currency_code,
            'margin_value' => $this->margin_value,
            'personal_rating' => $this->personal_rating,
            'ranking' => $this->ranking,
            'account_manager_name' => $this->account_manager_name,
            'service_level_agreement_id' => $this->service_level_agreement_id,
            'sale_unit_name' => $this->sale_unit_name,
            'drop_in' => $this->drop_in,
            'lead_source_name' => $this->lead_source_name,
            'has_higher_sla' => $this->has_higher_sla,
            'is_multi_year' => $this->is_multi_year,
            'has_additional_hardware' => $this->has_additional_hardware,
            'has_service_credits' => $this->has_service_credits,
            'remarks' => $this->remarks,
            'notes' => $this->notes,
            'campaign_name' => $this->campaign_name,
            'competition_name' => $this->competition_name,

            'sale_action_name' => $this->sale_action_name,
            'order_in_pipeline_stage' => $this->order_in_pipeline_stage,

            'suppliers_grid' => $this->opportunitySuppliers,

            'status' => $this->status,
            'status_reason' => $this->status_reason,
            'quotes_exist' => (bool)$this->quotes_exist,
            'quote' => QuoteOfOpportunityResource::make($this->worldwideQuotes->first()),

            'validation_result' => $this->validationResult,

            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }

    private static function formatBasePriceValue(float $value): string
    {
        return format('number', $value, prepend: Currencies::getSymbol(setting('base_currency')));
    }
}
