<?php

namespace App\Http\Resources\WorldwideQuote;

use App\Enum\ContractQuoteStage;
use App\Enum\PackQuoteStage;
use App\Models\Quote\WorldwideQuote;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class WorldwideQuoteState extends JsonResource
{
    public array $availableIncludes = [
        'versions',

        'company',
        'company.addresses',

        'assets',
        'assets.machineAddress',
        'assets.machineAddress.country',
        'assetsGroups',
        'assetsGroups.assets',
        'assetsGroups.assets.machineAddress',
        'assetsGroups.assets.machineAddress.country',

        'opportunity',
        'opportunity.addresses',
        'opportunity.addresses.country',
        'opportunity.contacts',
        'opportunity.accountManager',
        'opportunity.primaryAccount',
        'opportunity.primaryAccount.addresses',
        'opportunity.primaryAccount.addresses.country',
        'opportunity.primaryAccount.contacts',
        'opportunity.primaryAccountContact',

        'worldwideDistributions',
        'quoteCurrency',
        'buyCurrency',
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

    public array $translatedIncludes = [
        'versions' => 'versions:id,worldwide_quote_id,user_id,user_version_sequence_number,updated_at',
        'company' => 'activeVersion.company',
        'company.addresses' => 'activeVersion.company.addresses',
        'assets' => 'activeVersion.assets',
        'assets.machineAddress' => 'activeVersion.assets.machineAddress',
        'assets.machineAddress.country' => 'activeVersion.assets.machineAddress.country',
        'assetsGroups' => 'activeVersion.assetsGroups',
        'assetsGroups.assets' => 'activeVersion.assetsGroups.assets',
        'assetsGroups.assets.machineAddress' => 'activeVersion.assetsGroups.assets.machineAddress',
        'assetsGroups.assets.machineAddress.country' => 'activeVersion.assetsGroups.assets.machineAddress.country',
        'worldwideDistributions' => 'activeVersion.worldwideDistributions',
        'quoteCurrency' => 'activeVersion.quoteCurrency',
        'buyCurrency' => 'activeVersion.buyCurrency',
        'outputCurrency' => 'activeVersion.outputCurrency',
        'quoteTemplate' => 'activeVersion.quoteTemplate',

        'opportunity.addresses' => 'activeVersion.addresses',
        'opportunity.addresses.country' => 'activeVersion.addresses.country',
        'opportunity.contacts' => 'activeVersion.contacts',

        'worldwideDistributions.opportunitySupplier' => 'activeVersion.worldwideDistributions.opportunitySupplier',
        'worldwideDistributions.distributorFile' => 'activeVersion.worldwideDistributions.distributorFile',
        'worldwideDistributions.mappingRow' => 'activeVersion.worldwideDistributions.mappingRow',
        'worldwideDistributions.mapping' => 'activeVersion.worldwideDistributions.mapping',
        'worldwideDistributions.scheduleFile' => 'activeVersion.worldwideDistributions.scheduleFile',
        'worldwideDistributions.vendors' => 'activeVersion.worldwideDistributions.vendors',
        'worldwideDistributions.country' => 'activeVersion.worldwideDistributions.country',
        'worldwideDistributions.distributionCurrency' => 'activeVersion.worldwideDistributions.distributionCurrency',
        'worldwideDistributions.mappedRows' => 'activeVersion.worldwideDistributions.mappedRows',
        'worldwideDistributions.rowsGroups' => 'activeVersion.worldwideDistributions.rowsGroups',
        'worldwideDistributions.rowsGroups.rows' => 'activeVersion.worldwideDistributions.rowsGroups.rows',
        'worldwideDistributions.addresses' => 'activeVersion.worldwideDistributions.addresses',
        'worldwideDistributions.contacts' => 'activeVersion.worldwideDistributions.contacts',
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

        /** @var User $user */
        $user = $request->user();

        return [
            'id' => $this->id,

            'acting_user_is_version_owner' => optional($user)->getKey() === $this->activeVersion->user_id,

            'active_version_id' => $this->active_version_id,
            'active_version_user_id' => $this->activeVersion->user_id,
            'user_version_sequence_number' => $this->activeVersion->user_version_sequence_number,

            'versions' => $this->whenLoaded('versions'),

            'contract_type_id' => $this->contract_type_id,
            'sales_order_id' => $this->sales_order_id,

            'user_id' => $this->user_id,

            'quote_number' => $this->quote_number,

            'opportunity_id' => $this->opportunity_id,
            'opportunity' => $this->when($this->relationLoaded('opportunity') || $this->activeVersion->relationLoaded('addresses') || $this->activeVersion->relationLoaded('contacts'), function () {
                /** @var WorldwideQuote|WorldwideQuoteState $this */

                return tap(QuoteOpportunity::make($this->opportunity), function (QuoteOpportunity $resource) {

                    /** @var WorldwideQuote|WorldwideQuoteState $this */

                    $additionalData = [];

                    if ($this->activeVersion->relationLoaded('addresses')) {
                        $additionalData['addresses'] = $this->activeVersion->addresses;
                    }

                    if ($this->activeVersion->relationLoaded('contacts')) {
                        $additionalData['contacts'] = $this->activeVersion->contacts;
                    }

                    $resource->additional($additionalData);

                });

            }),

            'company_id' => $this->activeVersion->company_id,
            'company' => $this->whenLoaded('company'),

            'quote_template_id' => $this->activeVersion->quote_template_id,
            'quote_template' => value(function () {
                /** @var WorldwideQuote $this */
                if (!$this->activeVersion->relationLoaded('quoteTemplate')) {
                    return new MissingValue();
                }

                return $this->activeVersion->quoteTemplate;
            }),

            'quote_currency_id' => $this->activeVersion->quote_currency_id,
            'quote_currency' => value(function () {
                /** @var WorldwideQuote $this */
                if (!$this->activeVersion->relationLoaded('quoteCurrency')) {
                    return new MissingValue();
                }

                return $this->activeVersion->quoteCurrency;
            }),

            'buy_currency_id' => $this->activeVersion->buy_currency_id,
            'buy_currency' => value(function () {
                /** @var WorldwideQuote $this */
                if (!$this->activeVersion->relationLoaded('buyCurrency')) {
                    return new MissingValue();
                }

                return $this->activeVersion->buyCurrency;
            }),

            'output_currency_id' => $this->activeVersion->output_currency_id,
            'output_currency' => value(function () {
                /** @var WorldwideQuote $this */
                if (!$this->activeVersion->relationLoaded('outputCurrency')) {
                    return new MissingValue();
                }

                return $this->activeVersion->outputCurrency;
            }),

            'actual_exchange_rate' => $this->actualExchangeRate,
            'target_exchange_rate' => (float)value(function () {
                /** @var WorldwideQuote $this */
                if (is_null($this->activeVersion->output_currency_id)) {
                    return $this->actualExchangeRate;
                }

                return $this->actualExchangeRate + ($this->actualExchangeRate * $this->activeVersion->exchange_rate_margin / 100);
            }),

            'assets' => value(function () {
                /** @var WorldwideQuote $this */
                if (!$this->activeVersion->relationLoaded('assets')) {
                    return new MissingValue();
                }

                return PackAsset::collection($this->activeVersion->assets);
            }),

            'assets_groups' => value(function () {
                /** @var WorldwideQuote $this */
                if (!$this->activeVersion->relationLoaded('assetsGroups')) {
                    return AssetsGroup::collection(new MissingValue());
                }

                return AssetsGroup::collection($this->activeVersion->assetsGroups);
            }),

            'use_groups' => (bool)$this->activeVersion->use_groups,

            'sort_assets_groups_column' => $this->activeVersion->sort_assets_groups_column,
            'sort_assets_groups_direction' => $this->activeVersion->sort_assets_groups_direction,
            'sort_rows_column' => $this->activeVersion->sort_rows_column,
            'sort_rows_direction' => $this->activeVersion->sort_rows_direction,

            'exchange_rate_margin' => $this->activeVersion->exchange_rate_margin,

            'summary' => $this->transform($this->activeVersion->summary, fn() => [
                'total_price' => number_format((float)$this->activeVersion->total_price, 2, '.', ''),
                'final_total_price' => number_format((float)$this->activeVersion->final_total_price, 2, '.', ''),
                'final_total_price_excluding_tax' => number_format((float)$this->activeVersion->final_total_price_excluding_tax, 2, '.', ''),
                'applicable_discounts_value' => number_format((float)$this->activeVersion->applicable_discounts_value, 2, '.', ''),
                'buy_price' => number_format((float)$this->activeVersion->buy_price, 2, '.', ''),
                'margin_percentage' => number_format((float)$this->activeVersion->margin_percentage, 2, '.', ''),
                'final_margin' => number_format((float)$this->activeVersion->final_margin, 2, '.', '')
            ]),

            'worldwide_distributions' => value(function () {
                /** @var WorldwideQuote $this */
                if (!$this->activeVersion->relationLoaded('worldwideDistributions')) {
                    return new MissingValue();
                }

                return WorldwideDistributionState::collection(
                    $this->activeVersion->worldwideDistributions->sortBy('created_at')->values()
                );
            }),

            'quote_expiry_date' => $this->activeVersion->quote_expiry_date,

            'completeness' => $this->activeVersion->completeness,

            'stage' => with($this->contract_type_id, function (string $contractType) {
                /** @var WorldwideQuote $this */
                if ($contractType === CT_PACK) {
                    return PackQuoteStage::getLabelOfValue($this->activeVersion->completeness);
                }

                return ContractQuoteStage::getLabelOfValue($this->activeVersion->completeness);

            }),

            'applicable_discounts' => $this->transform($this->activeVersion->applicableDiscounts, fn() => [
                'multi_year_discounts' => $this->activeVersion->applicableMultiYearDiscounts,
                'pre_pay_discounts' => $this->activeVersion->applicablePrePayDiscounts,
                'promotional_discounts' => $this->activeVersion->applicablePromotionalDiscounts,
                'sn_discounts' => $this->activeVersion->applicableSnDiscounts,
            ]),

            'predefined_discounts' => $this->transform($this->activeVersion->predefinedDiscounts, fn() => [
                'multi_year_discount' => $this->activeVersion->multiYearDiscount,
                'pre_pay_discount' => $this->activeVersion->prePayDiscount,
                'promotional_discount' => $this->activeVersion->promotionalDiscount,
                'sn_discount' => $this->activeVersion->snDiscount
            ]),

            'custom_discount' => $this->activeVersion->custom_discount,

            'quote_type' => $this->activeVersion->quote_type,
            'margin_method' => $this->activeVersion->margin_method,
            'margin_value' => $this->activeVersion->margin_value,

            'tax_value' => $this->activeVersion->tax_value,

            'buy_price' => $this->activeVersion->buy_price,

            'closing_date' => $this->activeVersion->closing_date ?? now()->toDateString(),

            'payment_terms' => $this->activeVersion->payment_terms,

            'pricing_document' => $this->activeVersion->pricing_document,
            'service_agreement_id' => $this->activeVersion->service_agreement_id,
            'system_handle' => $this->activeVersion->system_handle,
            'additional_details' => $this->activeVersion->additional_details,

            'additional_notes' => value(function () {

                /** @var WorldwideQuote|WorldwideQuoteState $this */

                if (is_null($this->activeVersion->note)) {
                    return null;
                }

                return $this->activeVersion->note->text;

            }),

            'status' => $this->status,
            'status_reason' => $this->status_reason,

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
