<?php

namespace App\Http\Resources\WorldwideQuote;

use App\Http\Resources\ImportedRow\MappingRow;
use App\Http\Resources\QuoteFile\StoredQuoteFile;
use App\Http\Resources\RowsGroup\RowsGroup;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Quote\DistributionFieldColumn;
use App\Models\Quote\WorldwideDistribution;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

class WorldwideDistributionState extends JsonResource
{
    public array $availableIncludes = [
        'distributorFile',
        'scheduleFile',
        'vendors',
        'country',
        'distributionCurrency',
//        'mappingRow',
        'mapping',
        'mappedRows',
        'rowsGroups',
        'rowsGroups.rows'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Quote\WorldwideDistribution|WorldwideDistributionState $this */

        return [
            'id' => $this->id,

            'replicated_distributor_quote_id' => $this->replicated_distributor_quote_id,

            'worldwide_quote_id' => $this->worldwide_quote_id,
            'opportunity_supplier_id' => $this->opportunity_supplier_id,

            'opportunity_supplier' => $this->whenLoaded('opportunitySupplier'),

            'distributor_file_id' => $this->distributor_file_id,
            'distributor_file' => $this->whenLoaded('distributorFile', fn() => StoredQuoteFile::make($this->distributorFile)),

            'mapping_row' => MappingRow::make($this->whenLoaded('mappingRow')),
            'mapping' => $this->whenLoaded('mapping'),

            'mapped_rows' => $this->whenLoaded('mappedRows'),

            'rows_groups' => RowsGroup::collection($this->whenLoaded('rowsGroups')),

            'sort_rows_column' => $this->sort_rows_column,
            'sort_rows_direction' => $this->sort_rows_direction,

            'sort_rows_groups_column' => $this->sort_rows_groups_column,
            'sort_rows_groups_direction' => $this->sort_rows_groups_direction,

            'schedule_file_id' => $this->schedule_file_id,
            'schedule_file' => $this->whenLoaded('scheduleFile', fn() => StoredQuoteFile::make($this->scheduleFile)),

            'data' => new stdClass, // for UI purposes

            'vendors' => $this->whenLoaded('vendors'),

            'country_id' => $this->country_id,
            'country' => $this->whenLoaded('country'),

            'addresses' => $this->whenLoaded('addresses', function () {
                /** @var WorldwideDistribution|WorldwideDistributionState $this */

                return $this->addresses;
            }),

            'contacts' => $this->whenLoaded('contacts', function () {
                /** @var WorldwideDistribution|WorldwideDistributionState $this */

                return $this->contacts;
            }),

            'margin_value' => $this->margin_value,
            'quote_type' => $this->quote_type,
            'margin_method' => $this->margin_method,

            'tax_value' => $this->tax_value,

            'applicable_discounts' => $this->transform($this->applicableDiscounts, fn() => [
                'multi_year_discounts' => $this->applicableMultiYearDiscounts,
                'pre_pay_discounts' => $this->applicablePrePayDiscounts,
                'promotional_discounts' => $this->applicablePromotionalDiscounts,
                'sn_discounts' => $this->applicableSnDiscounts,
            ]),

            'predefined_discounts' => $this->transform($this->predefinedDiscounts, fn() => [
                'multi_year_discount' => $this->multiYearDiscount,
                'pre_pay_discount' => $this->prePayDiscount,
                'promotional_discount' => $this->promotionalDiscount,
                'sn_discount' => $this->snDiscount
            ]),

            'distribution_currency_id' => $this->distribution_currency_id,
            'distribution_currency' => $this->whenLoaded('distributionCurrency'),

            'buy_currency_id' => $this->buy_currency_id,
            'buy_currency' => $this->whenLoaded('buyCurrency'),

            'distribution_expiry_date' => $this->distribution_expiry_date,

            'custom_discount' => transform($this->custom_discount, fn() => (float)$this->custom_discount),

            'buy_price' => transform($this->buy_price, fn() => number_format((float)$this->buy_price, 2, '.', '')),

            'margin_percentage_after_custom_discount' => $this->transform($this->margin_percentage_after_custom_discount, fn() => number_format((float)$this->margin_percentage_after_custom_discount, 2, '.', '')),
            'distribution_currency_quote_currency_exchange_rate_value' => transform($this->distribution_currency_quote_currency_exchange_rate_value, fn ($value) => (float)$value),
            'distribution_currency_quote_currency_exchange_rate_margin' => transform($this->distribution_currency_quote_currency_exchange_rate_margin, fn ($value) => (float)$value),

            'summary' => $this->transform($this->summary, fn() => [
                'total_price' => number_format((float)$this->total_price, 2, '.', ''),
                'final_total_price' => number_format((float)$this->final_total_price, 2, '.', ''),
                'final_total_price_excluding_tax' => number_format((float)$this->final_total_price_excluding_tax, 2, '.', ''),
                'applicable_discounts_value' => number_format((float)$this->applicable_discounts_value, 2, '.', ''),
                'buy_price' => number_format((float)$this->buy_price, 2, '.', ''),
                'margin_percentage' => number_format((float)$this->margin_percentage, 2, '.', ''),
                'final_margin' => number_format((float)$this->final_margin, 2, '.', '')
            ]),

            'calculate_list_price' => (bool)$this->calculate_list_price,

            'use_groups' => (bool)$this->use_groups,
            'pricing_document' => $this->pricing_document,
            'service_agreement_id' => $this->service_agreement_id,
            'system_handle' => $this->system_handle,
            'additional_details' => $this->additional_details,

            'cached_distribution_exchange_rate' => $this->distribution_exchange_rate,

            'purchase_order_number' => $this->purchase_order_number,
            'vat_number' => $this->vat_number,
            'additional_notes' => $this->additional_notes,
            'checkbox_status' => $this->checkbox_status,

            'is_imported' => !is_null($this->imported_at),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
