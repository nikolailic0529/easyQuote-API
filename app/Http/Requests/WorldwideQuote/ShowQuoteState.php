<?php

namespace App\Http\Requests\WorldwideQuote;

use App\Models\Data\Currency;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\DistributionFieldColumn;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Vendor;
use App\Queries\DiscountQueries;
use App\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc;
use App\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Database\Eloquent\Collection;
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
        'worldwide_distributions.mapping_row' => 'includeWorldwideDistributionsMappingRow',
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

            $parameters = ['model' => $worldwideQuote];

            $this->container->call([$this, 'sortWorldwideDistributionRowsWhenLoaded'], $parameters);
            $this->container->call([$this, 'sortWorldwideDistributionRowsGroupsWhenLoaded'], $parameters);
            $this->container->call([$this, 'includeExclusivityOfWorldwideDistributionRowsForCustomerWhenLoaded'], $parameters);
            $this->container->call([$this, 'sortWorldwideQuoteAssetsWhenLoaded'], $parameters);
            $this->container->call([$this, 'sortWorldwideQuoteAssetsGroupsWhenLoaded'], $parameters);
            $this->container->call([$this, 'includeExclusivityOfWorldwidePackQuoteAssetsForCustomerWhenLoaded'], $parameters);
            $this->container->call([$this, 'normalizeWorldwideQuoteAssetAttributesWhenLoaded'], $parameters);
            $this->container->call([$this, 'prepareWorldwideDistributionMappingWhenLoaded'], $parameters);
            $this->container->call([$this, 'loadCountryOfAddressesWhenLoaded'], $parameters);
            $this->container->call([$this, 'loadReferencedAddressDataOfAddressesWhenLoaded'], $parameters);
            $this->container->call([$this, 'loadReferencedContactDataOfContactsWhenLoaded'], $parameters);
        });
    }

    public function loadReferencedAddressDataOfAddressesWhenLoaded(WorldwideQuote $model)
    {
        if ($model->activeVersion->relationLoaded('addresses')) {

            $referencedAddressPivotsOfPrimaryAccount = $model->referencedAddressPivotsOfPrimaryAccount->pluck('is_default', 'address_id');

            foreach ($model->activeVersion->addresses as $address) {
                $address->setAttribute('is_default', (bool)($referencedAddressPivotsOfPrimaryAccount[$address->getKey()] ?? false));
            }
        }

        if ($model->activeVersion->relationLoaded('worldwideDistributions')) {

            $referencedAddressPivotsOfPrimaryAccount = $model->referencedAddressPivotsOfPrimaryAccount->pluck('is_default', 'address_id');

            foreach ($model->activeVersion->worldwideDistributions as $distributorQuote) {

                if ($distributorQuote->relationLoaded('addresses')) {

                    foreach ($distributorQuote->addresses as $address) {
                        $address->setAttribute('is_default', (bool)($referencedAddressPivotsOfPrimaryAccount[$address->getKey()] ?? false));
                    }

                }

            }

        }
    }

    public function loadReferencedContactDataOfContactsWhenLoaded(WorldwideQuote $model)
    {
        if ($model->activeVersion->relationLoaded('contacts')) {

            $referencedContactPivotsOfPrimaryAccount = $model->referencedContactPivotsOfPrimaryAccount->pluck('is_default', 'contact_id');

            foreach ($model->activeVersion->contacts as $contact) {
                $contact->setAttribute('is_default', (bool)($referencedContactPivotsOfPrimaryAccount[$contact->getKey()] ?? false));
            }
        }

        if ($model->activeVersion->relationLoaded('worldwideDistributions')) {

            $referencedContactPivotsOfPrimaryAccount = $model->referencedContactPivotsOfPrimaryAccount->pluck('is_default', 'contact_id');

            foreach ($model->activeVersion->worldwideDistributions as $distributorQuote) {

                if ($distributorQuote->relationLoaded('contacts')) {

                    foreach ($distributorQuote->contacts as $contact) {
                        $contact->setAttribute('is_default', (bool)($referencedContactPivotsOfPrimaryAccount[$contact->getKey()] ?? false));
                    }

                }

            }

        }
    }

    public function loadCountryOfAddressesWhenLoaded(WorldwideQuote $model)
    {
        if ($model->activeVersion->relationLoaded('addresses')) {
            $model->activeVersion->addresses->load('country');
        }

        if ($model->activeVersion->relationLoaded('worldwideDistributions')) {
            foreach ($model->activeVersion->worldwideDistributions as $distributorQuote) {
                if ($distributorQuote->relationLoaded('addresses')) {
                    $distributorQuote->addresses->load('country');
                }
            }
        }
    }

    public function includeCompanyLogo(WorldwideQuote $model)
    {
        if (!is_null($model->activeVersion->company)) {
            $model->activeVersion->company->setAttribute('logo', $model->activeVersion->company->logo);
        }
    }

    public function includeVendorsLogo(WorldwideQuote $model)
    {
        $model->activeVersion->worldwideDistributions->each(function (WorldwideDistribution $distribution) {
            $distribution->vendors->each(function (Vendor $vendor) {
                $vendor->setAttribute('logo', $vendor->logo);
            });
        });
    }

    public function includeCountryFlag(WorldwideQuote $model)
    {
        $model->activeVersion->worldwideDistributions->each(function (WorldwideDistribution $distribution) {
            if (!is_null($distribution->country)) {
                $distribution->country->setAttribute('flag', $distribution->country->flag);
            }
        });
    }

    public function prepareWorldwideDistributionMappingWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        $templateLoaded = $model->activeVersion->relationLoaded('quoteTemplate');

        if (!$model->activeVersion->relationLoaded('worldwideDistributions') || is_null($model->activeVersion->quoteTemplate)) {
            return;
        }

        $quoteTemplate = $model->activeVersion->quoteTemplate;

        $includesOriginalPrice = value(function (): bool {
            foreach ($this->input('include') ?? [] as $include) {
                if ($include === 'worldwide_distributions.mapping.original_price') {
                    return true;
                }
            }

            return false;
        });

        $model->activeVersion->worldwideDistributions->each(function (WorldwideDistribution $distribution) use ($quoteTemplate, $dataMapper, $includesOriginalPrice) {

            if (!$distribution->relationLoaded('mapping')) {
                return;
            }

            $mapping = [];


            foreach ($distribution->mapping as $column) {
                /** @var DistributionFieldColumn $column */

                $column->setAttribute('is_required', $dataMapper->isMappingFieldRequired($column->template_field_name));

                $column->setAttribute('template_field_header', $dataMapper->mapTemplateFieldHeader($column->template_field_name, $quoteTemplate) ?? $column->template_field_header);

                if ($includesOriginalPrice && $column->template_field_name === 'price') {
                    $mapping[] = tap(new DistributionFieldColumn(), function (DistributionFieldColumn $distributionFieldColumn) use ($column, $distribution) {
                        $distributionFieldColumn->worldwide_distribution_id = $distribution->getKey();
                        $distributionFieldColumn->template_field_id = null;
                        $distributionFieldColumn->importable_column_id = null;
                        $distributionFieldColumn->is_default_enabled = $column->is_default_enabled;
                        $distributionFieldColumn->is_editable = $column->is_editable;
                        $distributionFieldColumn->is_required = $column->is_required;
                        $distributionFieldColumn->sort = null;
                        $distributionFieldColumn->template_field_name = 'original_price';
                        $distributionFieldColumn->template_field_header = 'Original Price';
                    });

                    $column->is_editable = false;
                }

                $mapping[] = $column;
            }

            $distribution->setRelation('mapping', new Collection($mapping));

        });

        if (!$templateLoaded) {
            $model->activeVersion->unsetRelation('quoteTemplate');
        }
    }

    public function normalizeWorldwideQuoteAssetAttributesWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        if ($model->activeVersion->relationLoaded('assets')) {

            $model->activeVersion->assets->loadMissing('buyCurrency');

            foreach ($model->activeVersion->assets as $asset) {
                if ($asset->relationLoaded('machineAddress')) {
                    $asset->setAttribute('machine_address_string', WorldwideQuoteDataMapper::formatMachineAddressToString($asset->machineAddress));
                }

                $asset->setAttribute('buy_currency_code', transform($asset->buyCurrency, fn(Currency $currency) => $currency->code));
            }

        }

        if ($model->activeVersion->relationLoaded('assetsGroups')) {

            foreach ($model->activeVersion->assetsGroups as $assetsGroup) {

                if (false === $assetsGroup->relationLoaded('assets')) {
                    continue;
                }

                $assetsGroup->loadMissing(['assets.buyCurrency', 'assets.vendor:id,short_code']);

                foreach ($assetsGroup->assets as $asset) {

                    if ($asset->relationLoaded('machineAddress')) {
                        $asset->setAttribute('machine_address_string', WorldwideQuoteDataMapper::formatMachineAddressToString($asset->machineAddress));
                    }

                    $asset->setAttribute('buy_currency_code', transform($asset->buyCurrency, fn(Currency $currency) => $currency->code));
                    $asset->setAttribute('vendor_short_code', transform($asset->vendor, fn(Vendor $vendor) => $vendor->short_code));

                }

            }

        }
    }

    public function sortWorldwideQuoteAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        if ($model->activeVersion->relationLoaded('assets')) {

            $dataMapper->sortWorldwidePackQuoteAssets($model);

        }
    }

    public function includeExclusivityOfWorldwidePackQuoteAssetsForCustomerWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        if ($model->activeVersion->relationLoaded('assets')) {
            $dataMapper->markExclusivityOfWorldwidePackQuoteAssetsForCustomer($model, $model->activeVersion->assets);
        }

        if ($model->activeVersion->relationLoaded('assetsGroups')) {
            $groupedAssets = $model->activeVersion->assetsGroups->pluck('assets')->collapse()->all();

            $dataMapper->markExclusivityOfWorldwidePackQuoteAssetsForCustomer($model, new Collection($groupedAssets));
        }
    }

    public function sortWorldwideDistributionRowsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        foreach ($model->activeVersion->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('mappedRows')) {

                $dataMapper->sortWorldwideDistributionRows($distribution);

            }

        }
    }

    public function includeExclusivityOfWorldwideDistributionRowsForCustomerWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        foreach ($model->activeVersion->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('mappedRows')) {
                $dataMapper->markExclusivityOfWorldwideDistributionRowsForCustomer($distribution, $distribution->mappedRows);
            }

            if ($distribution->relationLoaded('rowsGroups')) {
                $groupedRows = $distribution->rowsGroups->pluck('rows')->collapse()->all();

                $dataMapper->markExclusivityOfWorldwideDistributionRowsForCustomer($distribution, new Collection($groupedRows));
            }

        }
    }

    public function sortWorldwideDistributionRowsGroupsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        foreach ($model->activeVersion->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('rowsGroups')) {

                $dataMapper->sortWorldwideDistributionRowsGroups($distribution);

            }

        }
    }

    public function sortWorldwideQuoteAssetsGroupsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper)
    {
        if ($model->activeVersion->relationLoaded('assetsGroups')) {

            $dataMapper->sortGroupsOfPackAssets($model);

        }
    }

    public function includeWorldwideQuoteSummaryAttribute(WorldwideQuote $model, \App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc $service)
    {
        $priceSummary = $service->calculatePriceSummaryOfQuote($model);

        $model->activeVersion->total_price = $priceSummary->total_price;
        $model->activeVersion->buy_price = $priceSummary->buy_price;
        $model->activeVersion->margin_percentage = $priceSummary->raw_margin;

        $model->activeVersion->final_total_price = $priceSummary->final_total_price;
        $model->activeVersion->final_total_price_excluding_tax = $priceSummary->final_total_price_excluding_tax;
        $model->activeVersion->applicable_discounts_value = $priceSummary->applicable_discounts_value;
        $model->activeVersion->final_margin = $priceSummary->final_margin;

        $model->activeVersion->setAttribute('summary', [
            'total_price' => $model->activeVersion->total_price,
            'final_total_price' => $model->activeVersion->final_total_price,
            'final_total_price_excluding_tax' => $model->activeVersion->final_total_price_excluding_tax,
            'applicable_discounts_value' => $model->activeVersion->applicable_discounts_value,
            'buy_price' => $model->activeVersion->buy_price,
            'margin_percentage' => $model->activeVersion->margin_percentage,
            'final_margin' => $model->activeVersion->final_margin,
        ]);
    }

    public function includeWorldwideDistributionsSummaryAttribute(WorldwideQuote $model, WorldwideDistributorQuoteCalc $service)
    {
        $model->activeVersion->worldwideDistributions->load([
            'multiYearDiscount:id,name,durations',
            'prePayDiscount:id,name,durations',
            'promotionalDiscount:id,name,value,minimum_limit',
            'snDiscount:id,name,value',
        ]);

        $model->activeVersion->worldwideDistributions->each(function (WorldwideDistribution $distribution) use ($service) {
            $distributorPriceSummary = $service->calculatePriceSummaryOfDistributorQuote($distribution);

            $distribution->total_price = $distributorPriceSummary->total_price;
            $distribution->margin_percentage = $distributorPriceSummary->raw_margin;
            $distribution->final_total_price = $distributorPriceSummary->final_total_price;
            $distribution->final_total_price_excluding_tax = $distributorPriceSummary->final_total_price_excluding_tax;
            $distribution->applicable_discounts_value = $distributorPriceSummary->applicable_discounts_value;
            $distribution->final_margin = $distributorPriceSummary->final_margin;
            $distribution->margin_percentage_after_custom_discount = $distributorPriceSummary->margin_after_custom_discount;
            $distribution->buy_price = $distributorPriceSummary->buy_price;

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

    public function includeWorldwideDistributionsPredefinedDiscounts(WorldwideQuote $model, WorldwideDistributorQuoteCalc $service)
    {
        $model->activeVersion->worldwideDistributions->load([
            'multiYearDiscount:id,name,durations',
            'prePayDiscount:id,name,durations',
            'promotionalDiscount:id,name,value,minimum_limit',
            'snDiscount:id,name,value',
        ])
            ->each(function (WorldwideDistribution $distribution) use ($service) {
                $applicableDiscounts = $service->predefinedDistributionDiscountsToApplicableDiscounts($distribution);

                $marginAfterDiscounts = $service->calculatePriceSummaryOfDistributorQuoteAfterPredefinedDiscounts($distribution, $applicableDiscounts);

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

    public function includeWorldwideQuotePredefinedDiscounts(WorldwideQuote $model, \App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc $service)
    {
        $model->activeVersion->load([
            'multiYearDiscount:id,name,durations',
            'prePayDiscount:id,name,durations',
            'promotionalDiscount:id,name,value,minimum_limit',
            'snDiscount:id,name,value',
        ]);

        $applicableDiscounts = $service->predefinedQuoteDiscountsToApplicableDiscounts($model);

        $model->activeVersion->setAttribute('predefinedDiscounts', [
            'multi_year_discount' => $model->activeVersion->multiYearDiscount,
            'pre_pay_discount' => $model->activeVersion->prePayDiscount,
            'promotional_discount' => $model->activeVersion->promotionalDiscount,
            'sn_discount' => $model->activeVersion->snDiscount,
        ]);
    }

    public function includeWorldwideQuoteApplicableDiscounts(WorldwideQuote $model, DiscountQueries $discountQueries)
    {
        $model->activeVersion->setAttribute('applicableMultiYearDiscounts',
            $discountQueries->activeMultiYearDiscountsQuery()->get()
        );

        $model->activeVersion->setAttribute('applicablePrePayDiscounts',
            $discountQueries->activePrePayDiscountsQuery()->get()
        );

        $model->activeVersion->setAttribute('applicablePromotionalDiscounts',
            $discountQueries->activePromotionalDiscountsQuery()->get()
        );

        $model->activeVersion->setAttribute('applicableSnDiscounts',
            $discountQueries->activeSnDiscountsQuery()->get()
        );

        $model->activeVersion->setAttribute('applicableDiscounts', [
            'multi_year_discounts' => $model->activeVersion->applicableMultiYearDiscounts,
            'pre_pay_discounts' => $model->activeVersion->applicablePrePayDiscounts,
            'promotional_discounts' => $model->activeVersion->applicablePromotionalDiscounts,
            'sn_discounts' => $model->activeVersion->applicableSnDiscounts,
        ]);
    }

    public function includeWorldwideDistributionsApplicableDiscounts(WorldwideQuote $model, DiscountQueries $discountQueries)
    {
        $model->activeVersion->worldwideDistributions->each(function (WorldwideDistribution $distribution) use ($discountQueries) {
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
