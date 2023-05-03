<?php

namespace App\Domain\Rescue\Resources\V1;

use App\Domain\Company\Resources\V1\CompanyResource;
use App\Domain\Rescue\Services\ContractViewService;
use App\Domain\Template\Resources\V1\TemplateResourceDesign;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractVersionResource extends JsonResource
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
        return [
            'id' => $this->id,
            'quote_id' => $this->quote_id,
            'user_id' => $this->user_id,
            'quote_template_id' => $this->quote_template_id,
            'contract_template_id' => $this->contract_template_id,
            'company_id' => $this->company_id,
            'vendor_id' => $this->vendor_id,
            'customer_id' => $this->customer_id,
            'country_margin_id' => $this->country_margin_id,
            'source_currency_id' => $this->source_currency_id,
            'target_currency_id' => $this->target_currency_id,
            'exchange_rate_margin' => $this->exchange_rate_margin,
            'actual_exchange_rate' => $this->actual_exchange_rate,
            'target_exchange_rate' => $this->target_exchange_rate,
            'type' => $this->type,
            'previous_state' => $this->previous_state,
            'completeness' => $this->completeness,
            'last_drafted_step' => $this->last_drafted_step,
            'margin_data' => $this->margin_data,
            'pricing_document' => $this->pricing_document,
            'service_agreement_id' => $this->service_agreement_id,
            'system_handle' => $this->system_handle,
            'additional_details' => $this->additional_details,
            'checkbox_status' => $this->checkbox_status,
            'closing_date' => optional($this->contract_date)->format(config('date.format_ui')),
            'additional_notes' => $this->additional_notes,
            'list_price' => $this->list_price,
            'calculate_list_price' => $this->calculate_list_price,
            'buy_price' => $this->buy_price,
            'group_description' => $this->group_description,
            'use_groups' => $this->use_groups && $this->has_group_description,
            'sort_group_description' => $this->sort_group_description,
            'has_group_description' => $this->has_group_description,
            'version_number' => $this->version_number,
            'hidden_fields' => ContractViewService::$contractHiddenFields,
            'sort_fields' => $this->sort_fields,
            'field_column' => $this->quote->activeVersionOrCurrent->field_column,
            'rows_data' => $this->rows_data,
            'margin_percentage_without_country_margin' => $this->margin_percentage_without_country_margin,
            'margin_percentage_without_discounts' => $this->margin_percentage_without_discounts,
            'user_margin_percentage' => $this->user_margin_percentage,
            'custom_discount' => $this->custom_discount,
            'quote_files' => [
                $this->priceList,
                $this->paymentSchedule,
            ],
            'contract_template' => TemplateResourceDesign::make($this->contractTemplate),
            'country_margin' => $this->countryMargin,
            'discounts' => $this->discounts,
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'rfq' => $this->customer->rfq,
                'valid_until' => $this->customer->valid_until,
                'valid_until_ui' => $this->customer->valid_until->format(config('date.format_ui')),
                'support_start' => $this->customer->support_start,
                'support_end' => $this->customer->support_end,
                'payment_terms' => $this->customer->payment_terms,
                'invoicing_terms' => $this->customer->invoicing_terms,
                'service_levels' => $this->customer->service_levels,
            ],
            'country' => $this->country,
            'vendor' => $this->vendor,
            'company' => CompanyResource::make($this->company),
            'template_fields' => $this->templateFields,
            'fields_columns' => $this->fields_columns,
            'versions_selection' => $this->versions_selection,
            'created_at' => $this->created_at,
        ];
    }
}
