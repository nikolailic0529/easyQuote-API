<?php

namespace App\Http\Resources\WorldwideQuote;

use App\Enum\ContractQuoteStage;
use App\Enum\PackQuoteStage;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class WorldwideQuoteState extends JsonResource
{
    public array $availableIncludes = [
        'company',
        'company.addresses',

        'assets',
        'assets.machineAddress',
        'assets.machineAddress.country',

        'opportunity',
        'opportunity.addresses',
        'opportunity.addresses.country',
        'opportunity.contacts',
        'opportunity.accountManager',
        'opportunity.primaryAccount',
        'opportunity.primaryAccountContact',

        'worldwideDistributions',
        'quoteCurrency',
        'outputCurrency',
        'quoteTemplate',

        'worldwideDistributions.opportunitySupplier',
        'worldwideDistributions.distributorFile',
        'worldwideDistributions.mappingRow',
        'worldwideDistributions.mapping',
        'worldwideDistributions.scheduleFile',
        'worldwideDistributions.vendors',
        'worldwideDistributions.country',
        'worldwideDistributions.distributionCurrency',
        'worldwideDistributions.mappedRows',
        'worldwideDistributions.rowsGroups',
        'worldwideDistributions.rowsGroups.rows',
        'worldwideDistributions.addresses',
        'worldwideDistributions.contacts',
    ];

    protected ?float $actualExchangeRate = null;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var WorldwideQuote|WorldwideQuoteState $this */

        return [
            'id' => $this->id,

            'contract_type_id' => $this->contract_type_id,
            'sales_order_id' => $this->sales_order_id,

            'user_id' => $this->user_id,

            'quote_number' => $this->quote_number,

            'opportunity_id' => $this->opportunity_id,
            'opportunity' => QuoteOpportunity::make($this->whenLoaded('opportunity')),

            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company'),

            'quote_template_id' => $this->quote_template_id,
            'quote_template' => $this->whenLoaded('quoteTemplate'),

            'quote_currency_id' => $this->quote_currency_id,
            'quote_currency' => $this->whenLoaded('quoteCurrency'),

            'output_currency_id' => $this->output_currency_id,
            'output_currency' => $this->whenLoaded('outputCurrency'),

            'actual_exchange_rate' => $this->actualExchangeRate,
            'target_exchange_rate' => (float)value(function () {
                if (is_null($this->output_currency_id)) {
                    return $this->actualExchangeRate;
                }

                return $this->actualExchangeRate + ($this->actualExchangeRate * $this->exchange_rate_margin / 100);
            }),

            'assets' => $this->whenLoaded('assets'),
            'sort_rows_column' => $this->sort_rows_column,
            'sort_rows_direction' => $this->sort_rows_direction,

            'exchange_rate_margin' => $this->exchange_rate_margin,

            'summary' => $this->transform($this->summary, fn() => [
                'total_price' => number_format((float)$this->total_price, 2, '.', ''),
                'final_total_price' => number_format((float)$this->final_total_price, 2, '.', ''),
                'final_total_price_excluding_tax' => number_format((float)$this->final_total_price_excluding_tax, 2, '.', ''),
                'applicable_discounts_value' => number_format((float)$this->applicable_discounts_value, 2, '.', ''),
                'buy_price' => number_format((float)$this->buy_price, 2, '.', ''),
                'margin_percentage' => number_format((float)$this->margin_percentage, 2, '.', ''),
                'final_margin' => number_format((float)$this->final_margin, 2, '.', '')
            ]),

            'worldwide_distributions' => $this->whenLoaded('worldwideDistributions', fn() => WorldwideDistributionState::collection($this->worldwideDistributions)),

            'quote_expiry_date' => $this->quote_expiry_date,

            'completeness' => $this->completeness,

            'stage' => with($this->contract_type_id, function (string $contractType) {
                if ($contractType === CT_PACK) {
                    return PackQuoteStage::getLabelOfValue($this->completeness);
                }

                return ContractQuoteStage::getLabelOfValue($this->completeness);

            }),

            'applicable_discounts' => $this->transform($this->applicableDiscounts, fn () => [
                'multi_year_discounts' => $this->applicableMultiYearDiscounts,
                'pre_pay_discounts' => $this->applicablePrePayDiscounts,
                'promotional_discounts' => $this->applicablePromotionalDiscounts,
                'sn_discounts' => $this->applicableSnDiscounts,
            ]),

            'predefined_discounts' => $this->transform($this->predefinedDiscounts, fn () => [
                'multi_year_discount' => $this->multiYearDiscount,
                'pre_pay_discount' => $this->prePayDiscount,
                'promotional_discount' => $this->promotionalDiscount,
                'sn_discount' => $this->snDiscount
            ]),

            'custom_discount' => $this->custom_discount,

            'quote_type' => $this->quote_type,
            'margin_method' => $this->margin_method,
            'margin_value' => $this->margin_value,

            'tax_value' => $this->tax_value,

            'buy_price' => $this->buy_price,

            'closing_date' => $this->closing_date ?? now()->toDateString(),

            'payment_terms' => $this->payment_terms,

            'pricing_document' => $this->pricing_document,
            'service_agreement_id' => $this->service_agreement_id,
            'system_handle' => $this->system_handle,
            'additional_details' => $this->additional_details,

            'additional_notes' => $this->additional_notes,

            'status' => $this->status,
            'status_reason' => $this->status_reason,

//            'checkbox_status' => $this->checkbox_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'activated_at' => $this->activated_at,
            'submitted_at' => $this->submitted_at
        ];
    }

    public function setActualExchangeRate(?float $rate): WorldwideQuoteState
    {
        $this->actualExchangeRate = $rate;

        return $this;
    }
}
