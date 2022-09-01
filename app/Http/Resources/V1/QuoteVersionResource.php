<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\ImportedRow\ImportedRowResource;
use App\Http\Resources\V1\TemplateRepository\TemplateResourceDesign;
use App\Models\Quote\Quote;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteVersionResource extends JsonResource
{
    public $availableIncludes = ['contractTemplate'];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Quote|QuoteVersionResource $this */

        return [
            'id' => $this->id,
            'user_id' => $this->activeVersionOrCurrent->user_id,
            'quote_template_id' => $this->activeVersionOrCurrent->quote_template_id,
            'contract_template_id' => $this->contract_template_id,
            'contract_id' => $this->contract->id,
            'company_id' => $this->activeVersionOrCurrent->company_id,
            'vendor_id' => $this->activeVersionOrCurrent->vendor_id,
            'customer_id' => $this->activeVersionOrCurrent->customer_id,
            'country_id' => $this->activeVersionOrCurrent->country_id,
            'country_margin_id' => $this->activeVersionOrCurrent->country_margin_id,
            'source_currency_id' => $this->activeVersionOrCurrent->source_currency_id,
            'target_currency_id' => $this->activeVersionOrCurrent->target_currency_id,
            'exchange_rate_margin' => $this->activeVersionOrCurrent->exchange_rate_margin,
            'actual_exchange_rate' => $this->activeVersionOrCurrent->actual_exchange_rate,
            'target_exchange_rate' => $this->activeVersionOrCurrent->target_exchange_rate,
            'type' => optional($this->activeVersionOrCurrent->countryMargin)->quote_type,
            'previous_state' => $this->activeVersionOrCurrent->previous_state,
            'completeness' => $this->activeVersionOrCurrent->completeness,
            'last_drafted_step' => $this->activeVersionOrCurrent->last_drafted_step,
            'margin_data' => [
                'quote_type' => optional($this->activeVersionOrCurrent->countryMargin)->quote_type,
            ],
            'pricing_document' => $this->activeVersionOrCurrent->pricing_document,
            'service_agreement_id' => $this->activeVersionOrCurrent->service_agreement_id,
            'system_handle' => $this->activeVersionOrCurrent->system_handle,
            'additional_details' => $this->activeVersionOrCurrent->additional_details,
            'checkbox_status' => $this->activeVersionOrCurrent->checkbox_status,
            'closing_date' => optional($this->activeVersionOrCurrent->closing_date)->format(config('date.format_ui')),
            'additional_notes' => $this->activeVersionOrCurrent->note?->text,
            'list_price' => $this->asDecimal((float)$this->activeVersionOrCurrent->totalPrice),
            'calculate_list_price' => $this->activeVersionOrCurrent->calculate_list_price,
            'buy_price_formatted' => $this->asDecimal((float)$this->activeVersionOrCurrent->buy_price),
            'buy_price' => transform($this->activeVersionOrCurrent->buy_price, fn($value) => (float)$value),
            'group_description' => $this->activeVersionOrCurrent->group_description,
            'use_groups' => $this->activeVersionOrCurrent->use_groups && $this->activeVersionOrCurrent->has_group_description,
            'sort_group_description' => $this->activeVersionOrCurrent->sort_group_description,
            'has_group_description' => $this->activeVersionOrCurrent->has_group_description,
            'version_number' => $this->activeVersionOrCurrent->version_number,
            'hidden_fields' => $this->activeVersionOrCurrent->hidden_fields,
            'sort_fields' => $this->activeVersionOrCurrent->sort_fields,
            'field_column' => $this->activeVersionOrCurrent->field_column,
            'rows_data' => ImportedRowResource::collection($this->activeVersionOrCurrent->firstRow),
            'margin_percentage_without_country_margin' => $this->activeVersionOrCurrent->margin_percentage_without_country_margin,
            'margin_percentage_without_discounts' => $this->activeVersionOrCurrent->margin_percentage_without_discounts,
            'user_margin_percentage' => $this->activeVersionOrCurrent->user_margin_percentage,
            'custom_discount' => $this->activeVersionOrCurrent->custom_discount,
            'quote_files' => collect([$this->activeVersionOrCurrent->priceList, $this->activeVersionOrCurrent->paymentSchedule])->filter(fn($file) => $file->exists)->values(),
            'quote_template' => TemplateResourceDesign::make($this->activeVersionOrCurrent->quoteTemplate),
            'contract_template' => TemplateResourceDesign::make($this->whenLoaded('contractTemplate')),
            'country_margin' => $this->activeVersionOrCurrent->countryMargin,
            'discounts' => $this->activeVersionOrCurrent->discounts,
            'belongs_to_eq_customer' => $this->activeVersionOrCurrent->customer->belongsToEasyQuote(),
            'customer' => [
                'id' => $this->activeVersionOrCurrent->customer->id,
                'name' => $this->activeVersionOrCurrent->customer->name,
                'rfq' => $this->activeVersionOrCurrent->customer->rfq,
                'valid_until' => $this->activeVersionOrCurrent->customer->valid_until,
                'valid_until_ui' => $this->activeVersionOrCurrent->customer->valid_until->format(config('date.format_ui')),
                'support_start' => $this->activeVersionOrCurrent->customer->support_start,
                'support_end' => $this->activeVersionOrCurrent->customer->support_end,
                'payment_terms' => $this->activeVersionOrCurrent->customer->payment_terms,
                'invoicing_terms' => $this->activeVersionOrCurrent->customer->invoicing_terms,
                'service_levels' => $this->activeVersionOrCurrent->customer->service_levels,
                'source' => $this->activeVersionOrCurrent->customer->source,
            ],
            'country' => $this->activeVersionOrCurrent->country,
            'vendor' => $this->activeVersionOrCurrent->vendor,
            'company' => CompanyResource::make($this->activeVersionOrCurrent->company),
            'template_fields' => $this->activeVersionOrCurrent->template_fields,
            'fields_columns' => $this->activeVersionOrCurrent->fields_columns,
            'versions_selection' => $this->versions_selection,
            'created_at' => $this->activeVersionOrCurrent->created_at,
            'submitted_at' => $this->activeVersionOrCurrent->submitted_at
        ];
    }

    private function asDecimal(float $value): string
    {
        return number_format($value, 2);
    }
}
