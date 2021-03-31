<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\WorldwideQuote\QuoteFinalTotalPrice;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\DistributionFieldColumn;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Template\QuoteTemplate;
use App\Models\Vendor;
use App\Queries\DiscountQueries;
use App\Services\WorldwideDistributionCalc;
use App\Services\WorldwideQuoteCalc;
use App\Services\WorldwideQuoteDataMapper;
use Illuminate\Foundation\Http\FormRequest;

class ShowQuoteState extends FormRequest
{
    protected array $availableAttributeIncludes = [
        'summary' => 'includeWorldwideQuoteSummaryAttribute',
        'company.logo' => 'includeCompanyLogo',
        'worldwide_distributions.vendors.logo' => 'includeVendorsLogo',
        'worldwide_distributions.country.flag' => 'includeCountryFlag',
        'predefined_discounts' => 'includeWorldwideQuotePredefinedDiscounts',
        'applicable_discounts' => 'includeWorldwideQuoteApplicableDiscounts',
        'worldwide_distributions.summary' => 'includeWorldwideDistributionsSummaryAttribute',
        'worldwide_distributions.predefined_discounts' => 'includeWorldwideDistributionsPredefinedDiscounts',
        'worldwide_distributions.applicable_discounts' => 'includeWorldwideDistributionsApplicableDiscounts',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function includeModelAttributes(WorldwideQuote $worldwideQuote): WorldwideQuote
    {
        return tap($worldwideQuote, function (WorldwideQuote $worldwideQuote) {

            $includes = (array)($this->query('include') ?? []);

            foreach ($includes as $include) {
                if (!isset($this->availableAttributeIncludes[$include])) {
                    continue;
                }

                $method = $this->availableAttributeIncludes[$include];

                if (method_exists($this, $method)) {
                    $this->container->call([$this, $method], ['model' => $worldwideQuote]);
                }
            }

            $this->container->call([$this, 'sortWorldwideDistributionRowsWhenLoaded'], ['model' => $worldwideQuote]);
            $this->container->call([$this, 'sortWorldwideDistributionRowsGroupsWhenLoaded'], ['model' => $worldwideQuote]);
            $this->container->call([$this, 'sortWorldwideQuoteAssetsWhenLoaded'], ['model' => $worldwideQuote]);
            $this->container->call([$this, 'normalizeWorldwideQuoteAssetAttributesWhenLoaded'], ['model' => $worldwideQuote]);
            $this->container->call([$this, 'prepareWorldwideDistributionMappingWhenLoaded'], ['model' => $worldwideQuote]);
        });
    }

    public function includeCompanyLogo(WorldwideQuote $model)
    {
        if (!is_null($model->company)) {
            $model->company->setAttribute('logo', $model->company->logo);
        }
    }

    public function includeVendorsLogo(WorldwideQuote $model)
    {
        $model->worldwideDistributions->each(function (WorldwideDistribution $distribution) {
           $distribution->vendors->each(function (Vendor $vendor) {
              $vendor->setAttribute('logo', $vendor->logo);
           });
        });
    }

    public function includeCountryFlag(WorldwideQuote $model)
    {
        $model->worldwideDistributions->each(function (WorldwideDistribution $distribution) {
            if (!is_null($distribution->country)) {
                $distribution->country->setAttribute('flag', $distribution->country->flag);
            }
        });
    }

    public function prepareWorldwideDistributionMappingWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        $templateLoaded = $model->relationLoaded('quoteTemplate');

        if (!$model->relationLoaded('worldwideDistributions') || is_null($model->quoteTemplate)) {
            return;
        }

        $quoteTemplate = $model->quoteTemplate;

        $model->worldwideDistributions->each(function (WorldwideDistribution $distribution) use ($quoteTemplate, $dataMapper) {

            if (!$distribution->relationLoaded('mapping')) {
                return;
            }

            $distribution->mapping->each(function (DistributionFieldColumn $column) use ($quoteTemplate, $dataMapper) {

                $column->setAttribute('is_required', $dataMapper->isMappingFieldRequired($column->template_field_name));

                $column->setAttribute('template_field_header', $dataMapper->mapTemplateFieldHeader($column->template_field_name, $quoteTemplate) ?? $column->template_field_header);

            });

        });

        if (!$templateLoaded) {
            $model->unsetRelation('quoteTemplate');
        }
    }

    public function normalizeWorldwideQuoteAssetAttributesWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        if ($model->relationLoaded('assets')) {

            foreach ($model->assets as $asset) {
                if ($asset->relationLoaded('machineAddress')) {
                    $asset->setAttribute('machine_address_string', WorldwideQuoteDataMapper::formatMachineAddressToString($asset->machineAddress));
                }
            }

        }
    }

    public function sortWorldwideQuoteAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        if ($model->relationLoaded('assets')) {

            $dataMapper->sortWorldwidePackQuoteAssets($model);

        }
    }

    public function sortWorldwideDistributionRowsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        foreach ($model->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('mappedRows')) {

                $dataMapper->sortWorldwideDistributionRows($distribution);

            }

        }
    }

    public function sortWorldwideDistributionRowsGroupsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        foreach ($model->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('rowsGroups')) {

                $dataMapper->sortWorldwideDistributionRowsGroups($distribution);

            }

        }
    }

    public function includeWorldwideQuoteSummaryAttribute(WorldwideQuote $model, WorldwideQuoteCalc $service)
    {
        $priceSummary = $service->calculatePriceSummaryOfQuote($model);

        $model->total_price = $priceSummary->total_price;
        $model->buy_price = $priceSummary->buy_price;
        $model->margin_percentage = $priceSummary->raw_margin;

        $model->final_total_price = $priceSummary->final_total_price;
        $model->final_total_price_excluding_tax = $priceSummary->final_total_price_excluding_tax;
        $model->applicable_discounts_value = $priceSummary->applicable_discounts_value;
        $model->final_margin = $priceSummary->final_margin;

        $model->setAttribute('summary', [
            'total_price' => $model->total_price,
            'final_total_price' => $model->final_total_price,
            'final_total_price_excluding_tax' => $model->final_total_price_excluding_tax,
            'applicable_discounts_value' => $model->applicable_discounts_value,
            'buy_price' => $model->buy_price,
            'margin_percentage' => $model->margin_percentage,
            'final_margin' => $model->final_margin,
        ]);
    }

    public function includeWorldwideDistributionsSummaryAttribute(WorldwideQuote $model, WorldwideDistributionCalc $service)
    {
        $model->worldwideDistributions->load([
            'multiYearDiscount:id,name,durations',
            'prePayDiscount:id,name,durations',
            'promotionalDiscount:id,name,value,minimum_limit',
            'snDiscount:id,name,value',
        ]);

        $model->worldwideDistributions->each(function (WorldwideDistribution $distribution) use ($service) {
            $distributorPriceSummary = $service->calculatePriceSummaryOfDistributorQuote($distribution);

            $distribution->total_price = $distributorPriceSummary->total_price;
            $distribution->margin_percentage = $distributorPriceSummary->raw_margin;
            $distribution->final_total_price = $distributorPriceSummary->final_total_price;
            $distribution->final_total_price_excluding_tax = $distributorPriceSummary->final_total_price_excluding_tax;
            $distribution->applicable_discounts_value = $distributorPriceSummary->applicable_discounts_value;
            $distribution->final_margin = $distributorPriceSummary->final_margin;
            $distribution->margin_percentage_after_custom_discount = $distributorPriceSummary->margin_after_custom_discount;

            $distribution->setAttribute('summary', [
                'total_price' => $distribution->total_price,
                'final_total_price' => $distribution->final_total_price,
                'final_total_price_excluding_tax' => $distribution->final_total_price_excluding_tax,
                'applicable_discounts_value' => $distribution->applicable_discounts_value,
                'buy_price' => $distribution->buy_price,
                'margin_percentage' => $distribution->margin_percentage,
                'final_margin' => $distribution->final_margin,
            ]);
        });
    }

    public function includeWorldwideDistributionsPredefinedDiscounts(WorldwideQuote $model, WorldwideDistributionCalc $service)
    {
        $model->worldwideDistributions->load([
            'multiYearDiscount:id,name,durations',
            'prePayDiscount:id,name,durations',
            'promotionalDiscount:id,name,value,minimum_limit',
            'snDiscount:id,name,value',
        ])
            ->each(function (WorldwideDistribution $distribution) use ($service) {
                $applicableDiscounts = $service->predefinedDistributionDiscountsToApplicableDiscounts($distribution);

                $marginAfterDiscounts = $service->calculatePriceSummaryAfterPredefinedDiscounts($distribution, $applicableDiscounts);

                $marginPercentageAttribute = 'margin_percentage';

                transform($distribution->multiYearDiscount, function (MultiYearDiscount $model) use ($marginPercentageAttribute, $marginAfterDiscounts) {
                    $model->mergeCasts([
                        $marginPercentageAttribute => 'decimal:2',
                    ]);
                    $model->setAttribute($marginPercentageAttribute, $marginAfterDiscounts->margin_after_multi_year_discount);
                });

                transform($distribution->prePayDiscount, function (PrePayDiscount $model) use ($marginPercentageAttribute, $marginAfterDiscounts) {
                    $model->mergeCasts([
                        $marginPercentageAttribute => 'decimal:2',
                    ]);
                    $model->setAttribute($marginPercentageAttribute, $marginAfterDiscounts->margin_after_pre_pay_discount);
                });

                transform($distribution->promotionalDiscount, function (PromotionalDiscount $model) use ($marginPercentageAttribute, $marginAfterDiscounts) {
                    $model->mergeCasts([
                        $marginPercentageAttribute => 'decimal:2',
                    ]);
                    $model->setAttribute($marginPercentageAttribute, $marginAfterDiscounts->margin_after_promotional_discount);
                });

                transform($distribution->snDiscount, function (SND $model) use ($marginPercentageAttribute, $marginAfterDiscounts) {
                    $model->mergeCasts([
                        $marginPercentageAttribute => 'decimal:2',
                    ]);
                    $model->setAttribute($marginPercentageAttribute, $marginAfterDiscounts->margin_after_sn_discount);
                });

                $distribution->setAttribute('predefinedDiscounts', [
                    'multi_year_discount' => $distribution->multiYearDiscount,
                    'pre_pay_discount' => $distribution->prePayDiscount,
                    'promotional_discount' => $distribution->promotionalDiscount,
                    'sn_discount' => $distribution->snDiscount,
                ]);
            });
    }

    public function includeWorldwideQuotePredefinedDiscounts(WorldwideQuote $model, WorldwideQuoteCalc $service)
    {
        $model->load([
            'multiYearDiscount:id,name,durations',
            'prePayDiscount:id,name,durations',
            'promotionalDiscount:id,name,value,minimum_limit',
            'snDiscount:id,name,value',
        ]);

        $applicableDiscounts = $service->predefinedQuoteDiscountsToApplicableDiscounts($model);

        $model->setAttribute('predefinedDiscounts', [
            'multi_year_discount' => $model->multiYearDiscount,
            'pre_pay_discount' => $model->prePayDiscount,
            'promotional_discount' => $model->promotionalDiscount,
            'sn_discount' => $model->snDiscount,
        ]);
    }

    public function includeWorldwideQuoteApplicableDiscounts(WorldwideQuote $model, DiscountQueries $discountQueries)
    {
        $model->setAttribute('applicableMultiYearDiscounts',
            $discountQueries->activeMultiYearDiscountsQuery()->get()
        );

        $model->setAttribute('applicablePrePayDiscounts',
            $discountQueries->activePrePayDiscountsQuery()->get()
        );

        $model->setAttribute('applicablePromotionalDiscounts',
            $discountQueries->activePromotionalDiscountsQuery()->get()
        );

        $model->setAttribute('applicableSnDiscounts',
            $discountQueries->activeSnDiscountsQuery()->get()
        );

        $model->setAttribute('applicableDiscounts', [
            'multi_year_discounts' => $model->applicableMultiYearDiscounts,
            'pre_pay_discounts' => $model->applicablePrePayDiscounts,
            'promotional_discounts' => $model->applicablePromotionalDiscounts,
            'sn_discounts' => $model->applicableSnDiscounts,
        ]);
    }

    public function includeWorldwideDistributionsApplicableDiscounts(WorldwideQuote $model, DiscountQueries $discountQueries)
    {
        $model->worldwideDistributions->each(function (WorldwideDistribution $distribution) use ($discountQueries) {
            $distribution->setAttribute('applicableMultiYearDiscounts',
                $discountQueries->applicableMultiYearDiscountsForDistributionQuery($distribution)->get(['id', 'name', 'durations'])
            );

            $distribution->setAttribute('applicablePrePayDiscounts',
                $discountQueries->applicablePrePayDiscountsForDistributionQuery($distribution)->get(['id', 'name', 'durations'])
            );

            $distribution->setAttribute('applicablePromotionalDiscounts',
                $discountQueries->applicablePromotionalDiscountsForDistributionQuery($distribution)->get(['id', 'name', 'value', 'minimum_limit'])
            );

            $distribution->setAttribute('applicableSnDiscounts',
                $discountQueries->applicableSnDiscountsForDistributionQuery($distribution)->get(['id', 'name', 'value'])
            );

            $distribution->setAttribute('applicableDiscounts', [
                'multi_year_discounts' => $distribution->applicableMultiYearDiscounts,
                'pre_pay_discounts' => $distribution->applicablePrePayDiscounts,
                'promotional_discounts' => $distribution->applicablePromotionalDiscounts,
                'sn_discounts' => $distribution->applicableSnDiscounts,
            ]);
        });
    }
}
