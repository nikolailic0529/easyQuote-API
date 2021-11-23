<?php

namespace App\Services\WorldwideQuote;

use App\Contracts\Services\ManagesExchangeRates;
use App\DTO\Discounts\ImmutablePriceSummaryData;
use App\DTO\Template\TemplateElement;
use App\DTO\WorldwideQuote\Export\AggregationField;
use App\DTO\WorldwideQuote\Export\AssetData;
use App\DTO\WorldwideQuote\Export\AssetField;
use App\DTO\WorldwideQuote\Export\AssetsGroupData;
use App\DTO\WorldwideQuote\Export\DistributionSummary;
use App\DTO\WorldwideQuote\Export\PaymentData;
use App\DTO\WorldwideQuote\Export\PaymentScheduleField;
use App\DTO\WorldwideQuote\Export\QuotePriceData;
use App\DTO\WorldwideQuote\Export\QuoteSummary;
use App\DTO\WorldwideQuote\Export\TemplateAssets;
use App\DTO\WorldwideQuote\Export\TemplateData;
use App\DTO\WorldwideQuote\Export\WorldwideDistributionData;
use App\DTO\WorldwideQuote\Export\WorldwideQuotePreviewData;
use App\DTO\WorldwideQuote\SalesOrderCompanyData;
use App\DTO\WorldwideQuote\WorldwideQuoteToSalesOrderData;
use App\Enum\AddressType;
use App\Enum\VAT;
use App\Models\Address;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Image;
use App\Models\Opportunity;
use App\Models\Quote\DistributionFieldColumn;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\SalesOrderTemplate;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Models\WorldwideQuoteAssetsGroup;
use App\Queries\MappedRowQueries;
use App\Queries\WorldwideQuoteAssetQueries;
use App\Services\Formatter\FormatterDelegatorService;
use App\Services\ThumbHelper;
use App\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc;
use App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
use App\Support\PriceParser;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use function optional;
use function tap;
use function transform;
use function value;
use function with;
use const BD_WORLDWIDE;
use const CT_CONTRACT;
use const CT_PACK;

class WorldwideQuoteDataMapper
{

    /** @var bool[]|null */
    protected ?array $requiredFieldsDictionary = null;

    public function __construct(protected Config                        $config,
                                protected WorldwideQuoteCalc            $worldwideQuoteCalc,
                                protected WorldwideDistributorQuoteCalc $worldwideDistributionCalc,
                                protected ManagesExchangeRates          $exchangeRateService,
                                protected WorldwideQuoteAssetQueries    $assetQueries,
                                protected MappedRowQueries              $mappedRowQueries,
                                protected FormatterDelegatorService     $formatter)
    {
    }

    public function isMappingFieldRequired(WorldwideQuote $quote, string $field): bool
    {
        // When `contract duration` is checked,
        // the date_from & date_to fields come as optional
        // regardless on configuration
        if (in_array($field, ['date_from', 'date_to']) && $quote->opportunity->is_contract_duration_checked) {
            return false;
        }

        $this->requiredFieldsDictionary ??= array_map(fn(string $name) => true, array_flip($this->config['quote-mapping.worldwide_quote.required_fields'] ?? []));

        return $this->requiredFieldsDictionary[$field] ?? false;
    }

    public function mapTemplateFieldHeader(string $fieldName, QuoteTemplate $template): ?string
    {
        $templateDataHeaders = $template->data_headers;
        $defaultDataHeaders = QuoteTemplate::dataHeadersDictionary();

        if (isset($templateDataHeaders[$fieldName])) {
            return $templateDataHeaders[$fieldName]['value'];
        }

        if (isset($defaultDataHeaders[$fieldName])) {
            return $defaultDataHeaders[$fieldName]['value'];
        }

        return $fieldName;
    }

    public function mapWorldwideQuoteSalesOrderData(WorldwideQuote $worldwideQuote): WorldwideQuoteToSalesOrderData
    {
        if ($worldwideQuote->contract_type_id === CT_CONTRACT) {
            return $this->mapContractWorldwideQuoteSalesOrderData($worldwideQuote);
        }

        if ($worldwideQuote->contract_type_id === CT_PACK) {
            return $this->mapPackWorldwideQuoteSalesOrderData($worldwideQuote);
        }

        throw new \RuntimeException('Unsupported Quote Contract Type to map into Sales Order Data.');
    }

    private function mapContractWorldwideQuoteSalesOrderData(WorldwideQuote $worldwideQuote): WorldwideQuoteToSalesOrderData
    {
        $activeVersion = $worldwideQuote->activeVersion;

        $companyData = new SalesOrderCompanyData([
            'id' => $activeVersion->company->getKey(),
            'name' => $activeVersion->company->name,
            'logo_url' => transform($activeVersion->company->logo, function (array $logo) {
                return $logo['x3'] ?? '';
            }),
        ]);

        $distributions = $activeVersion->worldwideDistributions()->with('country:id,name,iso_3166_2,flag', 'vendors:id,name,short_code', 'vendors.image')->get(['id', 'country_id']);

        $vendors = $distributions->pluck('vendors')->collapse()->unique('id')->values();

        $vendorsData = $vendors->map(fn(Vendor $vendor) => [
            'id' => $vendor->getKey(),
            'name' => $vendor->name,
            'logo_url' => transform($vendor->logo, function (array $logo) {
                return $logo['x3'] ?? '';
            }),
        ])->all();

        $countries = $distributions->pluck('country')->unique('id')->values();

        $countriesData = $countries->map(fn(Country $country) => [
            'id' => $country->getKey(),
            'name' => $country->name,
            'flag_url' => $country->flag,
        ])->all();

        $templates = SalesOrderTemplate::query()
            ->where('business_division_id', BD_WORLDWIDE)
            ->where('contract_type_id', CT_CONTRACT)
            ->where('company_id', $activeVersion->company_id)
            ->whereNotNull('activated_at')
            ->get(['id', 'name']);

        $templatesData = $templates
            ->sortBy('name', SORT_NATURAL)
            ->map(fn(SalesOrderTemplate $template) => [
                'id' => $template->getKey(),
                'name' => $template->name,
            ])->all();

        $customer = $worldwideQuote->opportunity->primaryAccount;

        return new WorldwideQuoteToSalesOrderData([
            'worldwide_quote_id' => $worldwideQuote->getKey(),
            'worldwide_quote_number' => $worldwideQuote->quote_number,
            'vat_number' => transform($customer, fn(Company $company) => $company->vat) ?? '',
            'vat_type' => transform($customer, fn(Company $company) => $company->vat_type) ?? VAT::VAT_NUMBER,
            'company' => $companyData,
            'vendors' => $vendorsData,
            'countries' => $countriesData,
            'sales_order_templates' => $templatesData,
            'contract_type' => $worldwideQuote->contractType->type_short_name,
        ]);
    }

    private function mapPackWorldwideQuoteSalesOrderData(WorldwideQuote $worldwideQuote): WorldwideQuoteToSalesOrderData
    {
        $activeVersion = $worldwideQuote->activeVersion;

        $companyData = new SalesOrderCompanyData([
            'id' => $activeVersion->company->getKey(),
            'name' => $activeVersion->company->name,
            'logo_url' => transform($activeVersion->company->logo, function (array $logo) {
                return $logo['x3'] ?? '';
            }),
        ]);

        $vendorModelKeys = $activeVersion->assets()->distinct('vendor_id')->pluck('vendor_id')->all();

        $vendors = Vendor::query()->whereKey($vendorModelKeys)->with('image')->get(['id', 'name']);

        $vendorsData = $vendors->map(fn(Vendor $vendor) => [
            'id' => $vendor->getKey(),
            'name' => $vendor->name,
            'logo_url' => transform($vendor->logo, function (array $logo) {
                return $logo['x3'] ?? '';
            }),
        ])->all();

        $addressModelKeys = $activeVersion->assets()->distinct('machine_address_id')->pluck('machine_address_id')->all();

        $countryModelKeys = Address::query()
            ->whereKey($addressModelKeys)
            ->whereNotNull('country_id')
            ->distinct('country_id')
            ->pluck('country_id')
            ->all();

        $countries = Country::query()->whereKey($countryModelKeys)->get(['id', 'name', 'iso_3166_2', 'flag']);

        $countriesData = $countries->map(fn(Country $country) => [
            'id' => $country->getKey(),
            'name' => $country->name,
            'flag_url' => $country->flag,
        ])->all();

        $templates = SalesOrderTemplate::query()
            ->where('business_division_id', BD_WORLDWIDE)
            ->where('contract_type_id', CT_PACK)
            ->where('company_id', $activeVersion->company_id)
            ->whereNotNull('activated_at')
            ->get(['id', 'name']);

        $templatesData = $templates
            ->sortBy('name', SORT_NATURAL)
            ->map(fn(SalesOrderTemplate $template) => [
                'id' => $template->getKey(),
                'name' => $template->name,
            ])->all();

        $customer = $worldwideQuote->opportunity->primaryAccount;

        return new WorldwideQuoteToSalesOrderData([
            'worldwide_quote_id' => $worldwideQuote->getKey(),
            'worldwide_quote_number' => $worldwideQuote->quote_number,
            'vat_number' => transform($customer, fn(Company $company) => $company->vat) ?? '',
            'vat_type' => transform($customer, fn(Company $company) => $company->vat_type) ?? VAT::VAT_NUMBER,
            'company' => $companyData,
            'vendors' => $vendorsData,
            'countries' => $countriesData,
            'sales_order_templates' => $templatesData,
            'contract_type' => $worldwideQuote->contractType->type_short_name,
        ]);
    }

    public function mapWorldwideQuotePreviewData(WorldwideQuote $worldwideQuote): WorldwideQuotePreviewData
    {
        if (is_null($worldwideQuote->activeVersion->quoteTemplate)) {
            throw new \RuntimeException("Quote must have a Template to create an export data.");
        }

        $outputCurrency = $this->getQuoteOutputCurrency($worldwideQuote);

        $packAssets = $this->getPackQuoteAssetsData($worldwideQuote, $outputCurrency);

        $contractDuration = $this->formatOpportunityContractDuration($worldwideQuote->opportunity);

        $this->applyToAssetCollection(static function (AssetData $assetData) use ($contractDuration) {
            $assetData->contract_duration = $contractDuration;
        }, ...$packAssets);

        $packAssetNotes = implode("\n", $this->resolveNotesForAssets($packAssets, $worldwideQuote->activeVersion->quoteTemplate));

        return new WorldwideQuotePreviewData([
            'template_data' => $this->getTemplateData($worldwideQuote),
            'distributions' => $this->getContractQuoteDistributionsData($worldwideQuote, $outputCurrency),
            'pack_assets' => $packAssets,
            'pack_asset_fields' => $this->getPackAssetFields($worldwideQuote, $worldwideQuote->activeVersion->quoteTemplate),
            'asset_notes' => $packAssetNotes,
            'pack_assets_are_grouped' => (bool)$worldwideQuote->activeVersion->use_groups,
            'quote_summary' => $this->getQuoteSummary($worldwideQuote, $outputCurrency),
            'contract_type_name' => $worldwideQuote->contractType->type_short_name,
        ]);
    }

    private function getQuoteOutputCurrency(WorldwideQuote $worldwideQuote): Currency
    {
        $currency = with($worldwideQuote->activeVersion, function (WorldwideQuoteVersion $version) {

            if (is_null($version->output_currency_id)) {
                return $version->quoteCurrency;
            }

            return $version->outputCurrency;

        });

        return tap($currency, function (Currency $currency) use ($worldwideQuote) {

            $currency->exchange_rate_value = $this->exchangeRateService->getTargetRate(
                $worldwideQuote->activeVersion->quoteCurrency,
                $worldwideQuote->activeVersion->outputCurrency
            );

        });
    }

    public function getTemplateData(WorldwideQuote $quote, bool $useLocalAssets = false): TemplateData
    {
        $templateSchema = $quote->activeVersion->quoteTemplate->form_data;

        return tap(new TemplateData([
            'first_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['first_page'] ?? []),
            'assets_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['data_pages'] ?? []),
            'payment_schedule_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['payment_schedule'] ?? []),
            'last_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['last_page'] ?? []),
            'template_assets' => $this->getTemplateAssets($quote, $useLocalAssets),
        ]), function (TemplateData $templateData) {

            foreach ($templateData->first_page_schema as $templateElement) {

                foreach ($templateElement->children as $templateElementChild) {

                    foreach ($templateElementChild->controls as $templateElementChildControl) {

                        if ($templateElementChildControl->id === 'list_price') {

                            $templateElement->children = [];

                            continue 3;

                        }

                    }

                }

            }

        });
    }

    /**
     * @param array $pageSchema
     * @return \App\DTO\Template\TemplateElement[]
     */
    private function templatePageSchemaToArrayOfTemplateElement(array $pageSchema): array
    {
        return array_map(function (array $element) {

            return new TemplateElement([
                'children' => $element['child'] ?? [],
                'class' => $element['class'] ?? '',
                'css' => $element['css'] ?? '',
                'toggle' => filter_var($element['toggle'] ?? false, FILTER_VALIDATE_BOOL),
                'visibility' => filter_var($element['visibility'] ?? false, FILTER_VALIDATE_BOOL),
            ]);

        }, $pageSchema);
    }

    /**
     * @param WorldwideQuote $quote
     * @param bool $useLocalAssets
     * @return TemplateAssets
     */
    private function getTemplateAssets(WorldwideQuote $quote, bool $useLocalAssets = false): TemplateAssets
    {
        $company = $quote->activeVersion->company;

        $flags = ThumbHelper::WITH_KEYS;

        if ($useLocalAssets) {
            $flags |= ThumbHelper::ABS_PATH;
        }

        $templateAssets = [
            'logo_set_x1' => [],
            'logo_set_x2' => [],
            'logo_set_x3' => [],
            'company_logo_x1' => '',
            'company_logo_x2' => '',
            'company_logo_x3' => '',
        ];

        $logoSetX1 = [];
        $logoSetX2 = [];
        $logoSetX3 = [];

        $companyImages = transform($company->image, function (Image $image) use ($flags, $company) {

            return ThumbHelper::getLogoDimensionsFromImage(
                $image,
                $company->thumbnailProperties(),
                'company_',
                $flags
            );

        }, []);

        $templateAssets = array_merge($templateAssets, $companyImages);

        /** @var Collection<Vendor>|Vendor[] $vendors */

        $vendors = with($quote, function (WorldwideQuote $quote) {

            if ($quote->contract_type_id === CT_PACK) {

                return Vendor::query()
                    ->whereIn((new Vendor())->getQualifiedKeyName(), function (BaseBuilder $builder) use ($quote) {
                        return $builder->selectRaw('distinct(vendor_id)')
                            ->from('worldwide_quote_assets')
                            ->where($quote->activeVersion->assets()->getQualifiedForeignKeyName(), $quote->activeVersion->getKey())
                            ->whereNotNull('vendor_id')
                            ->where('is_selected', true);
                    })
                    ->has('image')
                    ->get();

            }

            if ($quote->contract_type_id === CT_CONTRACT) {

                $vendors = $quote->activeVersion->worldwideDistributions->load(['vendors' => function (BelongsToMany $relationship) {
                    $relationship->has('image');
                }])->pluck('vendors')->collapse();

                $vendors = new Collection($vendors);

                return $vendors->unique()->values();

            }

            return new Collection();
        });

        foreach ($vendors as $key => $vendor) {

            $vendorImages = ThumbHelper::getLogoDimensionsFromImage(
                $vendor->image,
                $vendor->thumbnailProperties(),
                '',
                $flags
            );

            $logoSetX1 = array_merge($logoSetX1, Arr::wrap($vendorImages['x1'] ?? []));
            $logoSetX2 = array_merge($logoSetX2, Arr::wrap($vendorImages['x2'] ?? []));
            $logoSetX3 = array_merge($logoSetX3, Arr::wrap($vendorImages['x3'] ?? []));

        }

        $composeLogoSet = function (array $logoSet) {
            static $whitespaceImg = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAfSURBVHgB7cqxAQAADAEw7f8/4wSDUeYcDYFHaLETBWmaBBDqHm1tAAAAAElFTkSuQmCC";

            if (empty($logoSet)) {
                return [];
            }

            if (count($logoSet) === 1) {
                return [array_shift($logoSet)];
            }

            $composed = [];

            $lastImgSource = array_pop($logoSet);

            foreach ($logoSet as $imgSource) {
                $composed[] = $imgSource;
                $composed[] = $whitespaceImg;
            }

            $composed[] = $lastImgSource;

            return $composed;
        };

        foreach ([
                     'logo_set_x1' => $logoSetX1,
                     'logo_set_x2' => $logoSetX2,
                     'logo_set_x3' => $logoSetX3,
                 ] as $key => $logoSet) {

            $templateAssets[$key] = $composeLogoSet($logoSet);
        }

        return new TemplateAssets($templateAssets);
    }

    /**
     * @param WorldwideQuote $worldwideQuote
     * @param Currency $outputCurrency
     * @return WorldwideDistributionData[]
     */
    public function getContractQuoteDistributionsData(WorldwideQuote $worldwideQuote, Currency $outputCurrency): array
    {
        if ($worldwideQuote->contract_type_id !== CT_CONTRACT) {
            return [];
        }

        $paymentScheduleFields = $this->getPaymentScheduleFields($worldwideQuote->activeVersion->quoteTemplate);

        $opportunity = $worldwideQuote->opportunity;

        return $worldwideQuote->activeVersion->worldwideDistributions->map(function (WorldwideDistribution $distribution) use (
            $paymentScheduleFields,
            $worldwideQuote,
            $outputCurrency
        ) {
            if (is_null($distribution->total_price)) {
                $distribution->total_price = $this->worldwideDistributionCalc->calculateTotalPriceOfDistributorQuote($distribution);
            }

            if (is_null($distribution->final_total_price)) {
                $distributionFinalTotalPrice = $this->worldwideDistributionCalc->calculateDistributionFinalTotalPrice($distribution, (float)$distribution->total_price);

                $distribution->final_total_price = $distributionFinalTotalPrice->final_total_price_value;
            }

            $priceSummaryOfDistributorQuote = $this->worldwideDistributionCalc->calculatePriceSummaryOfDistributorQuote($distribution);

            $priceValueCoeff = with($priceSummaryOfDistributorQuote, function (ImmutablePriceSummaryData $priceSummaryData): float {

                if ($priceSummaryData->total_price !== 0.0) {
                    return $priceSummaryData->final_total_price_excluding_tax / $priceSummaryData->total_price;
                }

                return 0.0;
            });

            $assetsData = $this->getDistributionAssetsData($distribution, $priceValueCoeff, $outputCurrency);

            $assetFields = $this->getDistributionAssetFields($distribution, $worldwideQuote->activeVersion->quoteTemplate);

            $mappedFieldsCount = count($assetFields);

            $paymentScheduleData = with($distribution->scheduleFile, function (?QuoteFile $scheduleFile) use ($priceValueCoeff, $outputCurrency, $distribution) {

                if (is_null($scheduleFile)) {
                    return [];
                }

                if (is_null($scheduleFile->scheduleData)) {
                    return [];
                }

                return $this->paymentScheduleToArrayOfPaymentData(BaseCollection::unwrap($scheduleFile->scheduleData->value), $priceValueCoeff, $outputCurrency);
            });

            /** @var Company $customer */

            $opportunity = $worldwideQuote->opportunity;
            $customer = $opportunity->primaryAccount;

            $serviceLevels = implode(', ', $customer->service_levels ?? []); // TODO: add the service levels.

            $opportunityStartDate = transform($opportunity->opportunity_start_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));
            $opportunityEndDate = transform($opportunity->opportunity_end_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));

            $coveragePeriod = value(function () use ($opportunityEndDate, $opportunityStartDate): string {
                if (!is_null($opportunityStartDate) && !is_null($opportunityEndDate)) {
                    return implode('&nbsp;', [
                        $this->formatter->format('date', $opportunityStartDate),
                        'to',
                        $this->formatter->format('date', $opportunityEndDate),
                    ]);
                }

                return '';
            });

            /** @var Address|null $quoteHardwareAddress */
            $quoteHardwareAddress = $distribution->addresses
                ->sortByDesc('pivot.is_default')
                ->first(fn(Address $address) => in_array($address->address_type, [AddressType::MACHINE, AddressType::HARDWARE, AddressType::EQUIPMENT], true));

            /** @var Address|null $quoteSoftwareAddress */
            $quoteSoftwareAddress = $distribution->addresses
                ->sortByDesc('pivot.is_default')
                ->first(fn(Address $address) => $address->address_type === AddressType::SOFTWARE);

            /** @var Contact|null $quoteHardwareContact */
            $quoteHardwareContact = $distribution->contacts
                ->sortByDesc('pivot.is_default')
                ->first(fn(Contact $contact) => $contact->contact_type === 'Hardware');

            /** @var Contact|null $quoteSoftwareContact */
            $quoteSoftwareContact = $distribution->contacts
                ->sortByDesc('pivot.is_default')
                ->first(fn(Contact $contact) => $contact->contact_type === 'Software');

            $assetNotes = implode("\n", $this->resolveNotesForAssets($assetsData, $worldwideQuote->activeVersion->quoteTemplate));

            $contractDuration = $this->formatOpportunityContractDuration($opportunity);

            $this->applyToAssetCollection(static function (AssetData $assetData) use ($contractDuration) {
                $assetData->contract_duration = $contractDuration;
            }, ...$assetsData);

            return new WorldwideDistributionData([
                'supplier' => [
                    'supplier_name' => (string)$distribution->opportunitySupplier->supplier_name,
                    'contact_name' => (string)$distribution->opportunitySupplier->contact_name,
                    'contact_email' => (string)$distribution->opportunitySupplier->contact_email,
                    'country_name' => (string)$distribution->opportunitySupplier->country_name,
                ],

                'vendors' => $distribution->vendors->pluck('name')->join(', '),
                'country' => $distribution->country->name,
                'assets_data' => $assetsData,
                'mapped_fields_count' => (int)$mappedFieldsCount,
                'assets_are_grouped' => (bool)$distribution->use_groups,
                'asset_fields' => $assetFields,
                'asset_notes' => $assetNotes,

                'payment_schedule_fields' => $paymentScheduleFields,
                'payment_schedule_data' => $paymentScheduleData,
                'has_payment_schedule_data' => !empty($paymentScheduleData),

                'equipment_address' => self::formatMachineAddressToString($quoteHardwareAddress),
                'hardware_phone' => $quoteHardwareContact->phone ?? '',
                'hardware_contact' => transform($quoteHardwareContact, function (Contact $contact) {
                        return implode(' ', [$contact->first_name, $contact->last_name]);
                    }) ?? '',

                'software_address' => self::formatMachineAddressToString($quoteSoftwareAddress),
                'software_phone' => $quoteSoftwareContact->phone ?? '',
                'software_contact' => transform($quoteSoftwareContact, function (Contact $contact) {
                        return implode(' ', [$contact->first_name, $contact->last_name]);
                    }) ?? '',

                'service_levels' => $serviceLevels,
                'coverage_period' => $coveragePeriod,
                'coverage_period_from' => $this->formatter->format('date', $opportunity->opportunity_start_date),
                'coverage_period_to' => $this->formatter->format('date', $opportunity->opportunity_end_date),
                'contract_duration' => $contractDuration,
                'is_contract_duration_checked' => (bool)$opportunity->is_contract_duration_checked,

                'additional_details' => $distribution->additional_details ?? '',
                'pricing_document' => $distribution->pricing_document ?? '',
                'service_agreement_id' => $distribution->service_agreement_id ?? '',
                'system_handle' => $distribution->system_handle ?? '',
                'purchase_order_number' => $distribution->purchase_order_number ?? '',
                'vat_number' => $distribution->vat_number ?? '',
            ]);
        })->all();
    }

    private function applyToAssetCollection(\Closure $closure, AssetData|AssetsGroupData ...$assetCollection): void
    {
        foreach ($assetCollection as $assetData) {
            if ($assetData instanceof AssetsGroupData) {

                foreach ($assetData->assets as $asset) {
                    $closure($asset);
                }

            } else {
                $closure($assetData);
            }
        }
    }

    public function formatOpportunityContractDuration(Opportunity $opportunity): string
    {
        if (is_null($opportunity->contract_duration_months)) {
            return '';
        }

        return CarbonInterval::months($opportunity->contract_duration_months)->cascade()->forHumans();
    }

    /**
     * @param QuoteTemplate $quoteTemplate
     * @return PaymentScheduleField[]
     */
    private function getPaymentScheduleFields(QuoteTemplate $quoteTemplate): array
    {
        $fields = static::getClassPublicProperties(PaymentData::class);

        $defaultHeaders = ['from' => 'From Date', 'to' => 'To Date', 'price' => 'Price'];

        return array_map(function (string $fieldName) use ($defaultHeaders, $quoteTemplate) {

            $fieldHeader = with($fieldName, function (string $fieldName) use ($defaultHeaders, $quoteTemplate) {

                $templateDataHeaders = $quoteTemplate->data_headers;
                $templateHeadersDictionary = QuoteTemplate::dataHeadersDictionary();

                if (isset($templateDataHeaders[$fieldName])) {
                    return $templateDataHeaders[$fieldName]['value'];
                }

                if (isset($templateHeadersDictionary[$fieldName])) {
                    return $templateHeadersDictionary[$fieldName]['value'];
                }

                return $defaultHeaders[$fieldName] ?? $fieldName;

            });

            return new PaymentScheduleField([
                'field_name' => $fieldName,
                'field_header' => $fieldHeader,
            ]);

        }, $fields);
    }

    private static function getClassPublicProperties(string $class): array
    {
        $reflection = new \ReflectionClass($class);
        $reflectionProperties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $fields = [];

        foreach ($reflectionProperties as $property) {
            if ($property->isStatic()) continue;

            $fields[] = $property->getName();
        }

        return $fields;
    }

    /**
     * @param WorldwideDistribution $distribution
     * @param float $priceValueCoeff
     * @param Currency $outputCurrency
     * @return AssetData[]|AssetsGroupData[]
     */
    private function getDistributionAssetsData(WorldwideDistribution $distribution, float $priceValueCoeff, Currency $outputCurrency): array
    {
        if (!$distribution->use_groups) {
            $distribution->load(['mappedRows' => function (Relation $builder) {
                $builder->where('is_selected', true);
            }]);

            $this->sortWorldwideDistributionRows($distribution);

            return $this->mappedRowsToArrayOfAssetData($distribution->mappedRows, $priceValueCoeff, $outputCurrency);
        }

        $distribution->load(['rowsGroups' => function (Relation $builder) {
            $builder->where('is_selected', true);
        }, 'rowsGroups.rows']);

        $this->sortWorldwideDistributionRowsGroups($distribution);

        return $distribution->rowsGroups->map(function (DistributionRowsGroup $rowsGroup) use ($priceValueCoeff, $outputCurrency, $distribution) {
            $assets = $this->mappedRowsToArrayOfAssetData($rowsGroup->rows, $priceValueCoeff * (float)$outputCurrency->exchange_rate_value, $outputCurrency);

            $groupTotalPrice = (float)$rowsGroup->rows_sum * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value;

            return new AssetsGroupData([
                'group_name' => $rowsGroup->group_name,
                'assets' => $assets,
                'group_total_price' => $this->formatter->format('number', $groupTotalPrice, prepend: $outputCurrency->symbol),
                'group_total_price_float' => $groupTotalPrice,
            ]);

        })->all();

    }

    /**
     * @param WorldwideDistribution $distribution
     */
    public function sortWorldwideDistributionRows(WorldwideDistribution $distribution): void
    {
        if (is_null($distribution->sort_rows_column) || $distribution->sort_rows_column === '') {
            return;
        }

        $results = $distribution->mappedRows->sortBy(
            $distribution->sort_rows_column,
            SORT_NATURAL,
            $distribution->sort_rows_direction === 'desc'
        )
            ->values();

        $distribution->setRelation('mappedRows', $results);
    }

    /**
     * @param Collection<MappedRow> $rows
     * @param float $priceValueCoeff
     * @param Currency $outputCurrency
     * @return AssetData[]
     */
    private function mappedRowsToArrayOfAssetData(Collection $rows, float $priceValueCoeff, Currency $outputCurrency): array
    {
        return $rows->map(function (MappedRow $row) use ($priceValueCoeff, $outputCurrency) {

            $assetPrice = (float)$row->price * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value;

            return new AssetData([
                'buy_currency_code' => '',
                'vendor_short_code' => '',
                'product_no' => $row->product_no ?? '',
                'service_sku' => $row->service_sku ?? '',
                'description' => $row->description ?? '',
                'serial_no' => transform($row->serial_no ?? '', function (string $serial) use ($row) {
                    if ($row->is_serial_number_generated) {
                        return "$serial **";
                    }

                    return $serial;
                }),
                'is_serial_number_generated' => (bool)$row->is_serial_number_generated,
                'date_from' => $this->formatter->format('date', $row->date_from),
                'date_to' => $this->formatter->format('date', $row->date_to),
                'qty' => $row->qty ?? 1,
                'price' => $this->formatter->format('number', $assetPrice, prepend: $outputCurrency->symbol),
                'price_float' => (float)$row->price * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value,
                'machine_address_string' => '',
                'pricing_document' => $row->pricing_document ?? '',
                'system_handle' => $row->system_handle ?? '',
                'searchable' => $row->searchable ?? '',
                'service_level_description' => $row->service_level_description ?? '',
            ]);
        })->all();
    }

    /**
     * @param WorldwideDistribution $distribution
     */
    public function sortWorldwideDistributionRowsGroups(WorldwideDistribution $distribution): void
    {
        if (is_null($distribution->sort_rows_groups_column) || $distribution->sort_rows_groups_column === '') {
            return;
        }

        $results = $distribution->rowsGroups->sortBy(
            $distribution->sort_rows_groups_column,
            SORT_NATURAL,
            $distribution->sort_rows_groups_direction === 'desc'
        )
            ->values();

        $distribution->setRelation('rowsGroups', $results);
    }

    private function getDistributionAssetFields(WorldwideDistribution $distribution, QuoteTemplate $quoteTemplate): array
    {
        $assetFields = [];

        $mapping = $distribution->mapping->keyBy('template_field_name')
            ->put('contract_duration', tap(new DistributionFieldColumn, function (DistributionFieldColumn $column) {
                $column->setAttribute('template_field_name', 'contract_duration');
                $column->setAttribute('is_editable', true);
            }));

        $dateTemplateFieldNames = value(static function () use ($distribution): array {
            if ($distribution->worldwideQuote->worldwideQuote->opportunity->is_contract_duration_checked) {
                return ['contract_duration'];
            }

            return ['date_from', 'date_to'];
        });

        $templateFieldNames = [
            'product_no',
            'description',
            'serial_no',
            'service_level_description',
            'service_sku',
            ...$dateTemplateFieldNames,
            'qty',
            'price',
        ];

        foreach ($templateFieldNames as $fieldName) {

            /** @var DistributionFieldColumn $column */

            $column = $mapping[$fieldName] ?? null;

            if (is_null($column)) {
                continue;
            }

            if (is_null($column->importable_column_id) && !$column->is_editable) {
                continue;
            }

            $assetFields[] = new AssetField([
                'field_name' => $column->template_field_name,
                'field_header' => self::resolveTemplateDataHeader($column->template_field_name, $quoteTemplate) ?? $column->template_field_name,
            ]);

        }

        return $assetFields;
    }

    /**
     * @param array $value
     * @param float $priceValueCoeff
     * @param Currency $outputCurrency
     * @return PaymentData[]
     */
    private function paymentScheduleToArrayOfPaymentData(array $value, float $priceValueCoeff, Currency $outputCurrency): array
    {
        return array_map(function (array $payment) use ($priceValueCoeff, $outputCurrency) {

            $paymentValue = (float)PriceParser::parseAmount($payment['price'] ?? '') * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value;

            return new PaymentData([
                'from' => $payment['from'],
                'to' => $payment['to'],
                'price' => $this->formatter->format('number', $paymentValue, prepend: $outputCurrency->symbol),
            ]);

        }, $value);
    }

    public static function formatMachineAddressToString(?Address $address): string
    {
        if (is_null($address)) {
            return '';
        }

        return implode(', ', array_filter([$address->address_1, $address->city, optional($address->country)->iso_3166_2]));
    }

    public function getPackQuoteAssetsData(WorldwideQuote $quote, Currency $outputCurrency): array
    {
        if ($quote->contract_type_id !== CT_PACK) {
            return [];
        }

        $quotePriceData = $this->getQuotePriceData($quote);

        $activeVersionOfQuote = $quote->activeVersion;

        if (!$activeVersionOfQuote->use_groups) {

            $activeVersionOfQuote->load(['assets' => function (Relation $builder) {
                $builder->where('is_selected', true);
            }]);

            $this->sortWorldwidePackQuoteAssets($quote);

            foreach ($activeVersionOfQuote->assets as $asset) {
                $asset->setAttribute('date_to', $quote->opportunity->opportunity_end_date);
            }

            return $this->worldwideQuoteAssetsToArrayOfAssetData($activeVersionOfQuote->assets, $quotePriceData->price_value_coefficient, $outputCurrency);

        }

        $quote->activeVersion->load(['assetsGroups' => function (Relation $builder) {
            $builder->where('is_selected', true);
        }, 'assetsGroups.assets', 'assetsGroups.assets.vendor:id,short_code']);

        $this->sortGroupsOfPackAssets($quote);

        return $quote->activeVersion->assetsGroups->map(function (WorldwideQuoteAssetsGroup $assetsGroup) use ($quote, $outputCurrency, $quotePriceData) {

            foreach ($assetsGroup->assets as $asset) {

                $asset->setAttribute('vendor_short_code', transform($asset->vendor, fn(Vendor $vendor) => $vendor->short_code));
                $asset->setAttribute('date_to', $quote->opportunity->opportunity_end_date);

            }

            $assets = $this->worldwideQuoteAssetsToArrayOfAssetData($assetsGroup->assets, $quotePriceData->price_value_coefficient, $outputCurrency);

            $groupTotalPrice = (float)$assetsGroup->assets_sum_price * $quotePriceData->price_value_coefficient * (float)$outputCurrency->exchange_rate_value;

            return new AssetsGroupData([
                'group_name' => $assetsGroup->group_name,
                'assets' => $assets,
                'group_total_price' => $this->formatter->format('number', $groupTotalPrice, prepend: $outputCurrency->symbol),
                'group_total_price_float' => $groupTotalPrice,
            ]);
        })->all();
    }

    private function getQuotePriceData(WorldwideQuote $worldwideQuote): QuotePriceData
    {
        return tap(new QuotePriceData(), function (QuotePriceData $quotePriceData) use ($worldwideQuote) {
            $priceSummary = $this->worldwideQuoteCalc->calculatePriceSummaryOfQuote($worldwideQuote);

            $quotePriceData->total_price_value = $priceSummary->total_price;
            $quotePriceData->total_price_value_after_margin = $priceSummary->total_price_after_margin;
            $quotePriceData->final_total_price_value = $priceSummary->final_total_price;
            $quotePriceData->final_total_price_value_excluding_tax = $priceSummary->final_total_price_excluding_tax;
            $quotePriceData->applicable_discounts_value = $priceSummary->applicable_discounts_value;

            if ($quotePriceData->total_price_value !== 0.0) {
                $quotePriceData->price_value_coefficient = $quotePriceData->final_total_price_value_excluding_tax / $quotePriceData->total_price_value;
            }
        });
    }

    public function sortWorldwidePackQuoteAssets(WorldwideQuote $quote): void
    {
        $activeVersion = $quote->activeVersion;

        $sortRowsColumn = $activeVersion->sort_rows_column;

        if (!is_string($sortRowsColumn) || $sortRowsColumn === '') {
            return;
        }

        $results = $activeVersion->assets->sortBy(
            function (WorldwideQuoteAsset $asset) use ($sortRowsColumn) {
                if ($sortRowsColumn === 'machine_address') {
                    return self::formatMachineAddressToString($asset->machineAddress);
                }

                return $asset->{$sortRowsColumn};
            },
            SORT_NATURAL,
            $activeVersion->sort_rows_direction === 'desc'
        )
            ->values();

        $quote->setRelation('assets', $results);
    }

    public function markExclusivityOfWorldwidePackQuoteAssetsForCustomer(WorldwideQuote $quote, Collection|WorldwideQuoteAsset $assets): void
    {
        if ($assets instanceof WorldwideQuoteAsset) {
            $assets = new Collection([$assets]);
        }

        $primaryAccount = $quote->opportunity->primaryAccount;

        if (is_null($primaryAccount)) {
            return;
        }

        $hashGenericAssetKey = function (Asset $asset) {
            return sprintf("%s.%s", $asset->serial_number, $asset->product_number);
        };

        $hashAssetKey = function (WorldwideQuoteAsset $asset) {
            return sprintf("%s.%s", $asset->serial_no, $asset->sku);
        };

        $sameGenericAssetDict = $this->assetQueries->sameGenericAssetsQuery($assets)
            ->whereDoesntHave('companies', function (Builder $builder) use ($primaryAccount) {
                $builder->whereKey($primaryAccount);
            })
            ->with('companies:companies.id,companies.user_id,companies.name')
            ->get()
            ->keyBy($hashGenericAssetKey);

        foreach ($assets as $asset) {
            /** @var WorldwideQuoteAsset $asset */

            $assetKeyHash = $hashAssetKey($asset);

            /** @var Asset|null $sameGenericAsset */
            $sameGenericAsset = $sameGenericAssetDict->get($assetKeyHash);

            $asset->setAttribute('is_customer_exclusive_asset', is_null($sameGenericAsset));

            if (!is_null($sameGenericAsset)) {
                $asset->setAttribute('owned_by_customer', $sameGenericAsset->companies->first());
            }
        }

    }

    public function markExclusivityOfWorldwideDistributionRowsForCustomer(WorldwideDistribution $distributorQuote, Collection|MappedRow $rows): void
    {
        if ($rows instanceof MappedRow) {
            $rows = new Collection([$rows]);
        }

        $primaryAccount = $distributorQuote->worldwideQuote->worldwideQuote->opportunity->primaryAccount;

        if (is_null($primaryAccount)) {
            return;
        }

        $hashMappedRowKey = function (MappedRow $mappedRow) {
            return sprintf("%s.%s", $mappedRow->serial_no, $mappedRow->product_no);
        };

        $hashGenericAssetKey = function (Asset $asset) {
            return sprintf("%s.%s", $asset->serial_number, $asset->product_number);
        };

        $sameGenericAssetDict = $this->mappedRowQueries->sameGenericAssetsQuery($rows)
            ->whereDoesntHave('companies', function (Builder $builder) use ($primaryAccount) {
                $builder->whereKey($primaryAccount);
            })
            ->with('companies:companies.id,companies.user_id,companies.name')
            ->get()
            ->keyBy($hashGenericAssetKey);

        foreach ($rows as $row) {

            /** @var MappedRow $row */

            $rowKeyHash = $hashMappedRowKey($row);

            /** @var Asset|null $sameGenericAsset */
            $sameGenericAsset = $sameGenericAssetDict->get($rowKeyHash);

            $row->setAttribute('is_customer_exclusive_asset', is_null($sameGenericAsset));

            if (!is_null($sameGenericAsset)) {
                $row->setAttribute('owned_by_customer', $sameGenericAsset->companies->first());
            }
        }

    }

    public function includeContractDurationToPackAssets(WorldwideQuote $quote, Collection|WorldwideQuoteAsset $assets): void
    {
        if ($assets instanceof WorldwideQuoteAsset) {
            $assets = new Collection([$assets]);
        }

        /** @var WorldwideQuoteAsset[] $assets */

        $attributes = [
            'contract_duration' => $this->formatOpportunityContractDuration($quote->opportunity),
            'contract_duration_months' => $quote->opportunity->contract_duration_months,
        ];

        foreach ($assets as $asset) {
            foreach ($attributes as $attribute => $value) {
                $asset->setAttribute($attribute, $value);
            }
        }
    }

    public function includeContractDurationToContractAssets(WorldwideDistribution $distributorQuote, Collection|MappedRow $rows): void
    {
        if ($rows instanceof MappedRow) {
            $rows = new Collection([$rows]);
        }

        /** @var MappedRow[] $rows */

        $attributes = [
            'contract_duration' => $this->formatOpportunityContractDuration($distributorQuote->worldwideQuote->worldwideQuote->opportunity),
            'contract_duration_months' => $distributorQuote->worldwideQuote->worldwideQuote->opportunity->contract_duration_months,
        ];

        foreach ($rows as $row) {
            foreach ($attributes as $attribute => $value) {
                $row->setAttribute($attribute, $value);
            }
        }
    }

    private function worldwideQuoteAssetsToArrayOfAssetData(Collection $assets, float $priceValueCoeff, Currency $outputCurrency): array
    {
        return $assets
            ->load('machineAddress.country')
            ->map(function (WorldwideQuoteAsset $asset) use ($outputCurrency, $priceValueCoeff) {
                $assetFloatPrice = (float)$asset->price * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value;

                return new AssetData([
                    'buy_currency_code' => $asset->buyCurrency?->code ?? '',
                    'vendor_short_code' => $asset->vendor_short_code ?? '',
                    'product_no' => $asset->sku ?? '',
                    'service_sku' => $asset->service_sku ?? '',
                    'description' => $asset->product_name ?? '',
                    'serial_no' => transform($asset->serial_no ?? '', function (string $serial) use ($asset) {
                        if ($asset->is_serial_number_generated) {
                            return "$serial **";
                        }

                        return $serial;
                    }),
                    'is_serial_number_generated' => (bool)$asset->is_serial_number_generated,
                    'date_from' => $this->formatter->format('date', $asset->expiry_date),
                    'date_to' => $this->formatter->format('date', $asset->date_to),
                    'qty' => 1,
                    'price' => $this->formatter->format('number', $assetFloatPrice, prepend: $outputCurrency->symbol),
                    'price_float' => $assetFloatPrice,
                    'machine_address_string' => transform($asset->machineAddress, function (Address $address): string {
                        return sprintf('%s %s', $address->address_1, optional($address->country)->iso_3166_2);
                    }, ''),
                    'pricing_document' => '',
                    'system_handle' => '',
                    'searchable' => '',
                    'service_level_description' => $asset->service_level_description ?? '',
                ]);
            })->all();
    }

    private function getPackAssetFields(WorldwideQuote $quote, QuoteTemplate $quoteTemplate): array
    {
        $assetFields = [];

        $assetDateFields = value(static function () use ($quote): array {
            if ($quote->opportunity->is_contract_duration_checked) {
                return ['contract_duration'];
            }

            return ['date_from', 'date_to'];
        });

        $assetFieldNames = [
            'vendor_short_code',
            'product_no',
            'description',
            'serial_no',
            'service_level_description',
            'service_sku',
            ...$assetDateFields,
            'price',
            'machine_address_string',
        ];

        foreach ($assetFieldNames as $fieldName) {
            $assetFields[] = new AssetField([
                'field_name' => $fieldName,
                'field_header' => self::resolveTemplateDataHeader($fieldName, $quoteTemplate) ?? $fieldName,
            ]);
        }

        return $assetFields;
    }

    private static function resolveTemplateDataHeader(string $fieldName, QuoteTemplate $quoteTemplate): ?string
    {
        $templateDataHeaders = $quoteTemplate->data_headers;
        $defaultDataHeaders = QuoteTemplate::dataHeadersDictionary();

        if (isset($templateDataHeaders[$fieldName])) {
            return $templateDataHeaders[$fieldName]['value'];
        }

        if (isset($defaultDataHeaders[$fieldName])) {
            return $defaultDataHeaders[$fieldName]['value'];
        }

        return null;
    }

    private function resolveNotesForAssets(array $assets, QuoteTemplate $quoteTemplate): array
    {
        $notes = [];

        $containGeneratedSerialNumber = collect($assets)
            ->contains(function (AssetData|AssetsGroupData $asset) {

                if ($asset instanceof AssetData) {
                    return $asset->is_serial_number_generated;
                }

                return collect($asset->assets)
                    ->contains(fn(AssetData $assetData) => $assetData->is_serial_number_generated);

            });

        if ($containGeneratedSerialNumber) {
            $autoGeneratedSerialNote = self::resolveTemplateDataHeader('auto_generated_serial_number_text', $quoteTemplate) ?? "Serial numbers are user's generated";

            $notes[] = "** $autoGeneratedSerialNote";
        }

        return $notes;
    }

    public function getQuoteSummary(WorldwideQuote $worldwideQuote, Currency $outputCurrency): QuoteSummary
    {
        $activeVersion = $worldwideQuote->activeVersion;

        $quoteDataAggregation = $worldwideQuote->contract_type_id === CT_CONTRACT
            ? $this->getContractQuoteDataAggregation($worldwideQuote, $outputCurrency)
            : [];

        $opportunity = $worldwideQuote->opportunity;

        /** @var Carbon|null $opportunityStartDate */
        $opportunityStartDate = transform($opportunity->opportunity_start_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));

        /** @var Carbon|null $opportunityEndDate */
        $opportunityEndDate = transform($opportunity->opportunity_end_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));

        /** @var Carbon|null $opportunityClosingDate */
        $opportunityClosingDate = transform($opportunity->opportunity_closing_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));

        /** @var Carbon|null $quoteExpiryDate */
        $quoteExpiryDate = transform($activeVersion->quote_expiry_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));

        $quotePriceData = $this->getQuotePriceData($worldwideQuote);

        /** @var Address|null $quoteHardwareAddress */
        $quoteHardwareAddress = $activeVersion->addresses
            ->sortByDesc('pivot.is_default')
            ->first(fn(Address $address) => in_array($address->address_type, [AddressType::MACHINE, AddressType::HARDWARE, AddressType::EQUIPMENT], true));

        /** @var Address|null $quoteSoftwareAddress */
        $quoteSoftwareAddress = $activeVersion->addresses
            ->sortByDesc('pivot.is_default')
            ->first(fn(Address $address) => $address->address_type === AddressType::SOFTWARE);

        /** @var Contact|null $quoteHardwareContact */
        $quoteHardwareContact = $activeVersion->contacts
            ->sortByDesc('pivot.is_default')
            ->first(fn(Contact $contact) => $contact->contact_type === 'Hardware');

        /** @var Contact|null $quoteSoftwareContact */
        $quoteSoftwareContact = $activeVersion->contacts
            ->sortByDesc('pivot.is_default')
            ->first(fn(Contact $contact) => $contact->contact_type === 'Software');

        $addressStringFormatter = function (?Address $address): string {
            if (is_null($address)) {
                return '';
            }

            return implode(', ', array_filter([$address->address_1, $address->city, optional($address->country)->iso_3166_2]));
        };

        $contactStringFormatter = function (?Contact $contact): string {
            if (is_null($contact)) {
                return '';
            }

            return implode(' ', [$contact->first_name, $contact->last_name]);
        };

        $coveragePeriod = value(function () use ($opportunityEndDate, $opportunityStartDate): string {
            if (!is_null($opportunityStartDate) && !is_null($opportunityEndDate)) {
                return implode('&nbsp;', [
                    $this->formatter->format('date', $opportunityStartDate),
                    'to',
                    $this->formatter->format('date', $opportunityEndDate),
                ]);
            }

            return '';
        });

        $footerNotes = [];

        if ($opportunity->is_opportunity_start_date_assumed || $opportunity->is_opportunity_end_date_assumed) {
            $footerNotes[] = "* The dates are based on assumption";
        }

        return new QuoteSummary([
            'company_name' => $activeVersion->company->name,
            'customer_name' => $opportunity->primaryAccount->name,
            'quotation_number' => $worldwideQuote->quote_number,
            'export_file_name' => $worldwideQuote->quote_number,

            'service_levels' => '',
            'invoicing_terms' => '',
            'payment_terms' => $activeVersion->payment_terms ?? '',

            'support_start' => $this->formatter->format('date', $opportunityStartDate),
            'support_start_assumed_char' => $opportunity->is_opportunity_start_date_assumed ? '*' : '',
            'support_end' => $this->formatter->format('date', $opportunityEndDate),
            'support_end_assumed_char' => $opportunity->is_opportunity_end_date_assumed ? '*' : '',
            'valid_until' => $this->formatter->format('date', $quoteExpiryDate),

            'contract_duration' => (string)transform($opportunity->contract_duration_months, fn (int $months) => CarbonInterval::months($months)->cascade()->forHumans()),
            'is_contract_duration_checked' => (bool)$opportunity->is_contract_duration_checked,

            'list_price' => $this->formatter->format('number', $quotePriceData->total_price_value_after_margin, prepend: $outputCurrency->symbol),
            'applicable_discounts' => $this->formatter->format('number', $quotePriceData->applicable_discounts_value, prepend: $outputCurrency->symbol),
            'final_price' => $this->formatter->format('number', $quotePriceData->final_total_price_value_excluding_tax, prepend: $outputCurrency->symbol),

            'quote_price_value_coefficient' => $quotePriceData->price_value_coefficient,

            'contact_name' => $contactStringFormatter($opportunity->primaryAccountContact),
            'contact_email' => $opportunity->primaryAccountContact?->email ?? '',
            'contact_phone' => $opportunity->primaryAccountContact?->phone ?? '',

            'quote_data_aggregation_fields' => $this->getQuoteDataAggregationFields($activeVersion->quoteTemplate),
            'quote_data_aggregation' => $quoteDataAggregation,

            'sub_total_value' => $this->formatter->format('number',$quotePriceData->final_total_price_value_excluding_tax, prepend: $outputCurrency->symbol),
            'total_value_including_tax' => $this->formatter->format('number',$quotePriceData->final_total_price_value, prepend: $outputCurrency->symbol),
            'grand_total_value' => $this->formatter->format('number',$quotePriceData->final_total_price_value, prepend: $outputCurrency->symbol),

            'equipment_address' => $addressStringFormatter($quoteHardwareAddress),
            'hardware_contact' => $contactStringFormatter($quoteHardwareContact),
            'hardware_phone' => $quoteHardwareContact?->phone ?? '',
            'software_address' => $addressStringFormatter($quoteSoftwareAddress),
            'software_contact' => $contactStringFormatter($quoteSoftwareContact),
            'software_phone' => $quoteSoftwareContact?->phone ?? '',
            'coverage_period' => $coveragePeriod,
            'coverage_period_from' => $this->formatter->format('date', $opportunityStartDate),
            'coverage_period_to' => $this->formatter->format('date', $opportunityEndDate),
            'additional_details' => $activeVersion->additional_details ?? '',
            'pricing_document' => $activeVersion->pricing_document ?? '',
            'service_agreement_id' => $activeVersion->service_agreement_id ?? '',
            'system_handle' => $activeVersion->system_handle ?? '',

            'footer_notes' => implode("\n", $footerNotes),
        ]);
    }

    private function getContractQuoteDataAggregation(WorldwideQuote $quote, Currency $outputCurrency): array
    {
        $quoteDataAggregation = [];

        $opportunity = $quote->opportunity;

        $duration = value(function () use ($opportunity): string {

            if (is_null($opportunity->opportunity_start_date) || is_null($opportunity->opportunity_end_date)) {
                return 'Unknown';
            }

            return Carbon::createFromFormat('Y-m-d', $opportunity->opportunity_start_date)
                ->longAbsoluteDiffForHumans(Carbon::createFromFormat('Y-m-d', $opportunity->opportunity_end_date)->addDay(), 2);

        });

        foreach ($quote->activeVersion->worldwideDistributions as $worldwideDistribution) {
            /** @var WorldwideDistribution $worldwideDistribution */

            $priceSummaryOfDistributorQuote = $this->worldwideDistributionCalc->calculatePriceSummaryOfDistributorQuote($worldwideDistribution);

            $quoteDataAggregation[] = new DistributionSummary([
                'vendor_name' => $worldwideDistribution->vendors->pluck('name')->implode(', '),
                'country_name' => $worldwideDistribution->country->name,
                'duration' => $duration,
                'qty' => 1,
                'total_price' => $this->formatter->format('number', $priceSummaryOfDistributorQuote->final_total_price_excluding_tax, prepend: $outputCurrency->symbol),
            ]);
        }

        return $quoteDataAggregation;
    }

    /**
     * @param QuoteTemplate $quoteTemplate
     * @return AggregationField[]
     */
    private function getQuoteDataAggregationFields(QuoteTemplate $quoteTemplate): array
    {
        $fields = static::getClassPublicProperties(DistributionSummary::class);

        $fields = array_values(
            array_filter($fields, fn(string $fieldName) => !in_array($fieldName, ['duration'], true))
        );

        return array_map(function (string $fieldName) use ($quoteTemplate) {

            $fieldHeader = with($fieldName, function (string $fieldName) use ($quoteTemplate) {

                $templateDataHeaders = $quoteTemplate->data_headers;
                $templateHeadersDictionary = QuoteTemplate::dataHeadersDictionary();

                if (isset($templateDataHeaders[$fieldName])) {
                    return $templateDataHeaders[$fieldName]['value'];
                }

                if (isset($templateHeadersDictionary[$fieldName])) {
                    return $templateHeadersDictionary[$fieldName]['value'];
                }

                return $fieldName;

            });

            return new AggregationField([
                'field_name' => $fieldName,
                'field_header' => $fieldHeader,
            ]);

        }, $fields);
    }

    public function mapWorldwideQuotePreviewDataForExport(WorldwideQuote $worldwideQuote): WorldwideQuotePreviewData
    {
        if (is_null($worldwideQuote->activeVersion->quoteTemplate)) {
            throw new \RuntimeException("Quote must have a Template to create an export data.");
        }

        $outputCurrency = $this->getQuoteOutputCurrency($worldwideQuote);

        $packAssets = $this->getPackQuoteAssetsData($worldwideQuote, $outputCurrency);

        $contractDuration = $this->formatOpportunityContractDuration($worldwideQuote->opportunity);

        $this->applyToAssetCollection(static function (AssetData $assetData) use ($contractDuration) {
            $assetData->contract_duration = $contractDuration;
        }, ...$packAssets);

        $packAssetNotes = implode("\n", $this->resolveNotesForAssets($packAssets, $worldwideQuote->activeVersion->quoteTemplate));

        return new WorldwideQuotePreviewData([
            'template_data' => $this->getTemplateData($worldwideQuote, true),
            'distributions' => $this->getContractQuoteDistributionsData($worldwideQuote, $outputCurrency),
            'pack_assets' => $packAssets,
            'pack_asset_fields' => $this->getPackAssetFields($worldwideQuote, $worldwideQuote->activeVersion->quoteTemplate),
            'asset_notes' => $packAssetNotes,
            'pack_assets_are_grouped' => (bool)$worldwideQuote->activeVersion->use_groups,
            'quote_summary' => $this->getQuoteSummary($worldwideQuote, $outputCurrency),
            'contract_type_name' => $worldwideQuote->contractType->type_short_name,
        ]);
    }

    public function sortGroupsOfPackAssets(WorldwideQuote $quote): void
    {
        $sortColumn = (string)$quote->activeVersion->sort_assets_groups_column;

        if ($sortColumn === '') {
            return;
        }

        /** @var string $sortColumn */
        $sortColumn = with($sortColumn, function (string $sortColumn): string {

            if ($sortColumn === 'assets_sum') {
                return 'assets_sum_price';
            }

            return $sortColumn;

        });

        $results = $quote->activeVersion->assetsGroups
            ->sortBy($sortColumn, SORT_NATURAL, $quote->activeVersion->sort_assets_groups_direction === 'desc')
            ->values();

        $quote->activeVersion->setRelation('assetsGroups', $results);
    }

    public function collectMappingColumnsOfDistributorQuote(WorldwideDistribution $distributorQuote): array
    {
        /** @var \App\Models\QuoteFile\ImportedRow[] $mappingRows */
        $importableColumns = $distributorQuote->mappingRows()
            ->toBase()
            ->selectRaw("distinct(json_unquote(json_extract(columns_data, '$.*.importable_column_id'))) as importable_columns")
            ->pluck('importable_columns');

        $importableColumns = $importableColumns->reduce(function (array $carry, string $columns) {
            return array_merge($carry, json_decode($columns, true));
        }, []);

        /** @var array $importableColumns */

        if (empty($importableColumns)) {
            return [];
        }

        $importableColumns = array_values(array_unique($importableColumns));

        $columnValueQueryBuilder = function (string $columnKey) use ($distributorQuote): BaseBuilder {
            return $distributorQuote->mappingRows()->getQuery()->toBase()
                ->selectRaw(sprintf("json_unquote(json_extract(columns_data, '$.\"%s\".value')) as value", $columnKey))
                ->selectRaw(sprintf("'%s' as importable_column_id", $columnKey))
                ->selectRaw(sprintf("json_unquote(json_extract(columns_data, '$.\"%s\".header')) as header", $columnKey))
                ->whereRaw(sprintf("json_unquote(json_extract(columns_data, '$.\"%s\".value')) is not null", $columnKey))
                ->orderBy($distributorQuote->mappingRows()->qualifyColumn('created_at'))
                ->limit(1);
        };

        $unionQuery = $columnValueQueryBuilder(array_shift($importableColumns));

        foreach ($importableColumns as $columnKey) {
            $unionQuery->unionAll(
                $columnValueQueryBuilder($columnKey)
            );
        }

        $columnValues = $unionQuery->get();

        $columnsData = [];

        foreach ($columnValues as $columnValue) {
            $columnsData[$columnValue->importable_column_id] = [
                'value' => $columnValue->value,
                'header' => $columnValue->header,
                'importable_column_id' => $columnValue->importable_column_id,
            ];
        }

        return $columnsData;
    }
}
