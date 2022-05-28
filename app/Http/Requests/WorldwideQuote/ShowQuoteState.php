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
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAssetsGroup;
use App\Queries\DiscountQueries;
use App\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc;
use App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
use App\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class ShowQuoteState extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    protected function getOnDemandAttributeLoaders(): array
    {
        return [
            'summary' => $this->includeSummaryAttributeOfQuote(...),
            'company.logo' => $this->includeCompanyLogo(...),
            'worldwide_distributions.vendors.logo' => $this->includeVendorsLogo(...),
            'worldwide_distributions.country.flag' => $this->includeCountryFlag(...),
            'predefined_discounts' => $this->includePredefinedDiscountsOfQuote(...),
            'applicable_discounts' => $this->includeApplicableDiscountsOfQuote(...),
            'worldwide_distributions.summary' => $this->includeSummaryAttributeOfDistributorQuotes(...),
            'worldwide_distributions.predefined_discounts' => $this->includePredefinedDiscountsOfDistributorQuotes(...),
            'worldwide_distributions.applicable_discounts' => $this->includeApplicableDiscountsOfDistributorQuotes(...),
        ];
    }

    protected function getDefaultAttributeLoaders(): array
    {
        return [
            $this->sortContractAssetsWhenLoaded(...),
            $this->sortGroupsOfContractAssetsWhenLoaded(...),
            $this->includeExclusivityOfContractAssetsWhenLoaded(...),
            $this->sortPackAssetsWhenLoaded(...),
            $this->sortGroupsOfPackAssetsWhenLoaded(...),
            $this->includeExclusivityOfPackAssetsWhenLoaded(...),
            $this->normalizeContractAssetAttributesWhenLoaded(...),
            $this->normalizePackAssetAttributesWhenLoaded(...),
            $this->prepareMappingOfDistributorQuotesWhenLoaded(...),
            $this->loadCountryOfAddressesWhenLoaded(...),
            $this->loadReferencedAddressDataOfAddressesWhenLoaded(...),
            $this->loadReferencedContactDataOfContactsWhenLoaded(...),
            $this->populateContractDurationToPackAssetsWhenLoaded(...),
            $this->populateContractDurationToContractAssetsWhenLoaded(...),
        ];
    }

    public function includeModelAttributes(WorldwideQuote $worldwideQuote): WorldwideQuote
    {
        return tap($worldwideQuote, function (WorldwideQuote $worldwideQuote) {

            $includes = Arr::wrap($this->query('include'));

            $onDemandLoaders = $this->getOnDemandAttributeLoaders();
            $defaultLoaders = $this->getDefaultAttributeLoaders();

            $parameters = ['model' => $worldwideQuote];

            foreach ($includes as $include) {
                if (array_key_exists($include, $onDemandLoaders)) {
                    $loader = $onDemandLoaders[$include];
                    $this->container->call($loader, $parameters);
                }
            }

            foreach ($defaultLoaders as $loader) {
                $this->container->call($loader, $parameters);
            }

        });
    }

    public function populateContractDurationToPackAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        $opportunityWasLoaded = $model->relationLoaded('opportunity');

        if ($model->activeVersion->relationLoaded('assets')) {
            $dataMapper->includeContractDurationToPackAssets($model, $model->activeVersion->assets);
        }

        if ($model->activeVersion->relationLoaded('assetsGroups')) {
            $model->activeVersion->assetsGroups->each(function (WorldwideQuoteAssetsGroup $group) use ($dataMapper, $model) {
                if (!$group->relationLoaded('assets')) {
                    return;
                }

                $dataMapper->includeContractDurationToPackAssets($model, $group->assets);
            });
        }

        if (!$opportunityWasLoaded) {
            $model->unsetRelation('opportunity');
        }
    }

    public function populateContractDurationToContractAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        $opportunityWasLoaded = $model->relationLoaded('opportunity');

        foreach ($model->activeVersion->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('mappedRows')) {
                $dataMapper->includeContractDurationToContractAssets($distribution, $distribution->mappedRows);
            }

            if ($distribution->relationLoaded('rowsGroups')) {
                $distribution->rowsGroups->each(static function (DistributionRowsGroup $group) use ($dataMapper, $distribution) {
                    if (!$group->relationLoaded('rows')) {
                        return;
                    }

                    $dataMapper->includeContractDurationToContractAssets($distribution, $group->rows);
                });
            }

        }

        if (!$opportunityWasLoaded) {
            $model->unsetRelation('opportunity');
        }
    }

    public function loadReferencedAddressDataOfAddressesWhenLoaded(WorldwideQuote $model): void
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

    public function loadReferencedContactDataOfContactsWhenLoaded(WorldwideQuote $model): void
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

    public function loadCountryOfAddressesWhenLoaded(WorldwideQuote $model): void
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

    public function includeCompanyLogo(WorldwideQuote $model): void
    {
        if (!is_null($model->activeVersion->company)) {
            $model->activeVersion->company->setAttribute('logo', $model->activeVersion->company->logo);
        }
    }

    public function includeVendorsLogo(WorldwideQuote $model): void
    {
        $model->activeVersion->worldwideDistributions->each(function (WorldwideDistribution $distribution) {
            $distribution->vendors->each(function (Vendor $vendor) {
                $vendor->setAttribute('logo', $vendor->logo);
            });
        });
    }

    public function includeCountryFlag(WorldwideQuote $model): void
    {
        $model->activeVersion->worldwideDistributions->each(function (WorldwideDistribution $distribution) {
            if (!is_null($distribution->country)) {
                $distribution->country->setAttribute('flag', $distribution->country->flag);
            }
        });
    }

    public function prepareMappingOfDistributorQuotesWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        $templateLoaded = $model->activeVersion->relationLoaded('quoteTemplate');
        $opportunityLoaded = $model->relationLoaded('opportunity');

        if (!$model->activeVersion->relationLoaded('worldwideDistributions') || is_null($model->activeVersion->quoteTemplate)) {
            return;
        }

        $quoteTemplate = $model->activeVersion->quoteTemplate;

        $includesOriginalPrice = $this->collect('include')->contains('worldwide_distributions.mapping.original_price');

        $model->activeVersion->worldwideDistributions->each(function (WorldwideDistribution $distribution) use ($model, $quoteTemplate, $dataMapper, $includesOriginalPrice) {

            if (!$distribution->relationLoaded('mapping')) {
                return;
            }

            $mapping = [];

            // Prepend hidden machine address column.
            $mapping[] = tap(new DistributionFieldColumn(), function (DistributionFieldColumn $distributionFieldColumn) use ($distribution) {
                $distributionFieldColumn->worldwide_distribution_id = $distribution->getKey();
                $distributionFieldColumn->template_field_id = null;
                $distributionFieldColumn->importable_column_id = null;
                $distributionFieldColumn->is_default_enabled = false;
                $distributionFieldColumn->is_editable = true;
                $distributionFieldColumn->is_required = false;
                $distributionFieldColumn->is_mapping_visible = false;
                $distributionFieldColumn->is_preview_visible = true;
                $distributionFieldColumn->sort = null;

                $distributionFieldColumn->template_field_name = 'machine_address';
                $distributionFieldColumn->template_field_header = 'Machine Address';
            });


            foreach ($distribution->mapping as $column) {
                /** @var DistributionFieldColumn $column */

                // Skip the date columns, when contract duration is checked
                if ($model->opportunity->is_contract_duration_checked && in_array($column->template_field_name, ['date_from', 'date_to'])) {
                    continue;
                }

                $column->setAttribute('is_mapping_visible', true);
                $column->setAttribute('is_required', $dataMapper->isMappingFieldRequired($model, $column->template_field_name));

                $column->setAttribute('template_field_header', $dataMapper->mapTemplateFieldHeader($column->template_field_name, $quoteTemplate) ?? $column->template_field_header);

                if ($includesOriginalPrice && 'price' === $column->template_field_name) {
                    $mapping[] = tap(new DistributionFieldColumn(), function (DistributionFieldColumn $distributionFieldColumn) use ($column, $distribution) {
                        $distributionFieldColumn->worldwide_distribution_id = $distribution->getKey();
                        $distributionFieldColumn->template_field_id = null;
                        $distributionFieldColumn->importable_column_id = null;
                        $distributionFieldColumn->is_default_enabled = $column->is_default_enabled;
                        $distributionFieldColumn->is_editable = $column->is_editable;
                        $distributionFieldColumn->is_required = $column->is_required;
                        $distributionFieldColumn->is_mapping_visible = $column->is_mapping_visible;
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

        if (!$opportunityLoaded) {
            $model->unsetRelation('opportunity');
        }
    }

    public function normalizePackAssetAttributesWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        if ($model->activeVersion->relationLoaded('assets')) {

            $model->activeVersion->assets->loadMissing('buyCurrency');

            foreach ($model->activeVersion->assets as $asset) {
                if ($asset->relationLoaded('machineAddress')) {
                    $asset->setAttribute('machine_address_string', WorldwideQuoteDataMapper::formatAddressToString($asset->machineAddress));
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
                        $asset->setAttribute('machine_address_string', WorldwideQuoteDataMapper::formatAddressToString($asset->machineAddress));
                    }

                    $asset->setAttribute('buy_currency_code', transform($asset->buyCurrency, fn(Currency $currency) => $currency->code));
                    $asset->setAttribute('vendor_short_code', transform($asset->vendor, fn(Vendor $vendor) => $vendor->short_code));

                }

            }

        }
    }

    public function normalizeContractAssetAttributesWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {

        $includeMachineAddressString = static function (Collection $rows): void {
            $rows->each(function (MappedRow $row) {

                if ($row->relationLoaded('machineAddress')) {
                    $row->setAttribute('machine_address_string', WorldwideQuoteDataMapper::formatAddressToString($row->machineAddress));
                }

            });
        };

        foreach ($model->activeVersion->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('mappedRows')) {
                $includeMachineAddressString($distribution->mappedRows);
            }

            if ($distribution->relationLoaded('rowsGroups')) {
                $distribution->rowsGroups->each(function (DistributionRowsGroup $group) use ($includeMachineAddressString) {
                    $includeMachineAddressString($group->rows);
                });
            }

        }
    }

    public function sortPackAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        if ($model->activeVersion->relationLoaded('assets')) {

            $dataMapper->sortWorldwidePackQuoteAssets($model);

        }
    }

    public function includeExclusivityOfPackAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        if ($model->activeVersion->relationLoaded('assets')) {
            $dataMapper->markExclusivityOfWorldwidePackQuoteAssetsForCustomer($model, $model->activeVersion->assets);
        }

        if ($model->activeVersion->relationLoaded('assetsGroups')) {
            $groupedAssets = $model->activeVersion->assetsGroups->pluck('assets')->collapse()->all();

            $dataMapper->markExclusivityOfWorldwidePackQuoteAssetsForCustomer($model, new Collection($groupedAssets));
        }
    }

    public function sortContractAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        foreach ($model->activeVersion->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('mappedRows')) {

                $dataMapper->sortWorldwideDistributionRows($distribution);

            }

        }
    }

    public function includeExclusivityOfContractAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
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

    public function sortGroupsOfContractAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        foreach ($model->activeVersion->worldwideDistributions as $distribution) {

            if ($distribution->relationLoaded('rowsGroups')) {

                $dataMapper->sortWorldwideDistributionRowsGroups($distribution);

            }

        }
    }

    public function sortGroupsOfPackAssetsWhenLoaded(WorldwideQuote $model, WorldwideQuoteDataMapper $dataMapper): void
    {
        if ($model->activeVersion->relationLoaded('assetsGroups')) {

            $dataMapper->sortGroupsOfPackAssets($model);

        }
    }

    public function includeSummaryAttributeOfQuote(WorldwideQuote $model, WorldwideQuoteCalc $service): void
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

    public function includeSummaryAttributeOfDistributorQuotes(WorldwideQuote $model, WorldwideDistributorQuoteCalc $service): void
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

    public function includePredefinedDiscountsOfDistributorQuotes(WorldwideQuote $model, WorldwideDistributorQuoteCalc $service): void
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

    public function includePredefinedDiscountsOfQuote(WorldwideQuote $model, WorldwideQuoteCalc $service): void
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

    public function includeApplicableDiscountsOfQuote(WorldwideQuote $model, DiscountQueries $discountQueries): void
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

    public function includeApplicableDiscountsOfDistributorQuotes(WorldwideQuote $model, DiscountQueries $discountQueries): void
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
