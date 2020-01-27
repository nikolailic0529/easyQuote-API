<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TemplateRepository\TemplateResourceDesign;

class QuoteVersionResource extends JsonResource
{
    public $availableIncludes = ['contractTemplate'];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                                        => $this->id,
            'user_id'                                   => $this->usingVersion->user_id,
            'quote_template_id'                         => $this->usingVersion->quote_template_id,
            'contract_template_id'                      => $this->contract_template_id,
            'company_id'                                => $this->usingVersion->company_id,
            'vendor_id'                                 => $this->usingVersion->vendor_id,
            'customer_id'                               => $this->usingVersion->customer_id,
            'country_margin_id'                         => $this->usingVersion->country_margin_id,
            'type'                                      => $this->usingVersion->type,
            'completeness'                              => $this->usingVersion->completeness,
            'last_drafted_step'                         => $this->usingVersion->last_drafted_step,
            'margin_data'                               => $this->usingVersion->margin_data,
            'pricing_document'                          => $this->usingVersion->pricing_document,
            'service_agreement_id'                      => $this->usingVersion->service_agreement_id,
            'system_handle'                             => $this->usingVersion->system_handle,
            'additional_details'                        => $this->usingVersion->additional_details,
            'checkbox_status'                           => $this->usingVersion->checkbox_status,
            'closing_date'                              => $this->usingVersion->closing_date,
            'additional_notes'                          => $this->usingVersion->additional_notes,
            'list_price'                                => $this->usingVersion->list_price,
            'calculate_list_price'                      => $this->usingVersion->calculate_list_price,
            'buy_price'                                 => $this->usingVersion->buy_price,
            'group_description'                         => $this->usingVersion->group_description,
            'use_groups'                                => $this->usingVersion->use_groups,
            'sort_group_description'                    => $this->usingVersion->sort_group_description,
            'has_group_description'                     => $this->usingVersion->has_group_description,
            'is_version'                                => $this->usingVersion->is_version,
            'version_number'                            => $this->usingVersion->version_number,
            'hidden_fields'                             => $this->usingVersion->hidden_fields,
            'sort_fields'                               => $this->usingVersion->sort_fields,
            'field_column'                              => $this->usingVersion->field_column,
            'rows_data'                                 => $this->usingVersion->rows_data,
            'margin_percentage_without_country_margin'  => $this->usingVersion->margin_percentage_without_country_margin,
            'margin_percentage_without_discounts'       => $this->usingVersion->margin_percentage_without_discounts,
            'user_margin_percentage'                    => $this->usingVersion->user_margin_percentage,
            'custom_discount'                           => $this->usingVersion->custom_discount,
            'quote_files'                               => $this->usingVersion->quoteFiles,
            'quote_template'                            => TemplateResourceDesign::make($this->usingVersion->quoteTemplate),
            'contract_template'                         => TemplateResourceDesign::make($this->whenLoaded('contractTemplate')),
            'country_margin'                            => $this->usingVersion->countryMargin,
            'discounts'                                 => $this->usingVersion->discounts,
            'customer'                                  => [
                'id' => $this->usingVersion->customer->id,
                'name' => $this->usingVersion->customer->name,
                'rfq' => $this->usingVersion->customer->rfq,
                'valid_until' => $this->usingVersion->customer->valid_until,
                'valid_until_ui' => $this->usingVersion->customer->valid_until_ui,
                'support_start' => $this->usingVersion->customer->support_start,
                'support_end' => $this->usingVersion->customer->support_end,
                'payment_terms' => $this->usingVersion->customer->payment_terms,
                'invoicing_terms' => $this->usingVersion->customer->invoicing_terms,
                'service_levels' => $this->usingVersion->customer->service_levels,
            ],
            'country'                                   => $this->usingVersion->country,
            'vendor'                                    => $this->usingVersion->vendor,
            'template_fields'                           => $this->usingVersion->templateFields,
            'fields_columns'                            => $this->usingVersion->fields_columns,
            'versions_selection'                        => $this->versions_selection,
            'created_at'                                => $this->usingVersion->created_at,
            'submitted_at'                              => $this->usingVersion->submitted_at
        ];
    }
}
