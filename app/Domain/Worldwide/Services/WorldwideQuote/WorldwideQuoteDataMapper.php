<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote;

use App\Domain\Address\Enum\AddressType;
use App\Domain\Address\Models\Address;
use App\Domain\Asset\Models\Asset;
use App\Domain\Company\Enum\VAT;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Enum\ContactType;
use App\Domain\Contact\Models\Contact;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\DocumentMapping\Queries\MappedRowQueries;
use App\Domain\DocumentProcessing\Services\PriceParser;
use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates;
use App\Domain\Formatting\Services\FormatterDelegatorService;
use App\Domain\Image\Models\Image;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\DataTransferObjects\TemplateElement;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\AggregationField;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetField;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetsGroupData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\PaymentData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\PaymentScheduleField;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\QuoteAggregation;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\QuotePriceData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\QuoteSummary;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\TemplateAssets;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\TemplateData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\WorldwideDistributionData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\WorldwideQuotePreviewData;
use App\Domain\Worldwide\DataTransferObjects\Quote\SalesOrderCompanyData;
use App\Domain\Worldwide\DataTransferObjects\Quote\WorldwideQuoteToSalesOrderData;
use App\Domain\Worldwide\Models\DistributionFieldColumn;
use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Domain\Worldwide\Queries\WorldwideQuoteAssetQueries;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\MessageBag;

class WorldwideQuoteDataMapper
{
    /** @var bool[]|null */
    protected ?array $requiredFieldsDictionary = null;

    public function __construct(
        protected Config $config,
        protected WorldwideQuoteCalc $worldwideQuoteCalc,
        protected WorldwideDistributorQuoteCalc $worldwideDistributionCalc,
        protected ManagesExchangeRates $exchangeRateService,
        protected WorldwideQuoteAssetQueries $assetQueries,
        protected MappedRowQueries $mappedRowQueries,
        protected FormatterDelegatorService $formatter
    ) {
    }

    public function isMappingFieldRequired(WorldwideQuote $quote, string $field): bool
    {
        // When `contract duration` is checked,
        // the date_from & date_to fields come as optional
        // regardless on configuration
        if (in_array($field, ['date_from', 'date_to']) && $quote->opportunity->is_contract_duration_checked) {
            return false;
        }

        $this->requiredFieldsDictionary ??= array_map(static fn (string $name) => true,
            array_flip($this->config['quote-mapping.worldwide_quote.required_fields'] ?? []));

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
        return match ($worldwideQuote->contract_type_id) {
            \CT_CONTRACT => $this->mapContractWorldwideQuoteSalesOrderData($worldwideQuote),
            \CT_PACK => $this->mapPackWorldwideQuoteSalesOrderData($worldwideQuote),
            default => throw new \RuntimeException('Unsupported Quote Contract Type to map into Sales Order Data.'),
        };
    }

    private function mapContractWorldwideQuoteSalesOrderData(WorldwideQuote $worldwideQuote
    ): WorldwideQuoteToSalesOrderData {
        $activeVersion = $worldwideQuote->activeVersion;

        $companyData = new SalesOrderCompanyData([
            'id' => $activeVersion->company->getKey(),
            'name' => $activeVersion->company->name,
            'logo_url' => \transform($activeVersion->company->logo, static function (array $logo) {
                return $logo['x3'] ?? '';
            }),
        ]);

        $distributions = $activeVersion->worldwideDistributions()
            ->with('country:id,name,iso_3166_2,flag', 'vendors:id,name,short_code', 'vendors.image')
            ->get(['id', 'country_id']);

        $vendors = $distributions->pluck('vendors')->collapse()->unique('id')->values();

        $vendorsData = $vendors->map(static fn (Vendor $vendor) => [
            'id' => $vendor->getKey(),
            'name' => $vendor->name,
            'logo_url' => \transform($vendor->logo, static function (array $logo) {
                return $logo['x3'] ?? '';
            }),
        ])->all();

        $countries = $distributions->pluck('country')->unique('id')->values();

        $countriesData = $countries->map(static fn (Country $country) => [
            'id' => $country->getKey(),
            'name' => $country->name,
            'flag_url' => $country->flag,
        ])->all();

        $templates = SalesOrderTemplate::query()
            ->where('business_division_id', \BD_WORLDWIDE)
            ->where('contract_type_id', \CT_CONTRACT)
            ->where('company_id', $activeVersion->company_id)
            ->whereNotNull('activated_at')
            ->get(['id', 'name']);

        $templatesData = $templates
            ->sortBy('name', SORT_NATURAL)
            ->map(static fn (SalesOrderTemplate $template) => [
                'id' => $template->getKey(),
                'name' => $template->name,
            ])->all();

        $customer = $worldwideQuote->opportunity->primaryAccount;

        return new WorldwideQuoteToSalesOrderData([
            'worldwide_quote_id' => $worldwideQuote->getKey(),
            'worldwide_quote_number' => $worldwideQuote->quote_number,
            'vat_number' => \transform($customer, static fn (Company $company) => $company->vat) ?? '',
            'vat_type' => \transform($customer, static fn (Company $company) => $company->vat_type) ?? VAT::VAT_NUMBER,
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
            'logo_url' => \transform($activeVersion->company->logo, static function (array $logo) {
                return $logo['x3'] ?? '';
            }),
        ]);

        $vendorModelKeys = $activeVersion->assets()->distinct('vendor_id')->pluck('vendor_id')->all();

        $vendors = Vendor::query()->whereKey($vendorModelKeys)->with('image')->get(['id', 'name']);

        $vendorsData = $vendors->map(static fn (Vendor $vendor) => [
            'id' => $vendor->getKey(),
            'name' => $vendor->name,
            'logo_url' => \transform($vendor->logo, static function (array $logo) {
                return $logo['x3'] ?? '';
            }),
        ])->all();

        $addressModelKeys = $activeVersion->assets()
            ->distinct('machine_address_id')
            ->pluck('machine_address_id')
            ->all();

        $countryModelKeys = Address::query()
            ->whereKey($addressModelKeys)
            ->whereNotNull('country_id')
            ->distinct('country_id')
            ->pluck('country_id')
            ->all();

        $countries = Country::query()->whereKey($countryModelKeys)->get(['id', 'name', 'iso_3166_2', 'flag']);

        $countriesData = $countries->map(static fn (Country $country) => [
            'id' => $country->getKey(),
            'name' => $country->name,
            'flag_url' => $country->flag,
        ])->all();

        $templates = SalesOrderTemplate::query()
            ->where('business_division_id', \BD_WORLDWIDE)
            ->where('contract_type_id', \CT_PACK)
            ->where('company_id', $activeVersion->company_id)
            ->whereNotNull('activated_at')
            ->get(['id', 'name']);

        $templatesData = $templates
            ->sortBy('name', SORT_NATURAL)
            ->map(static fn (SalesOrderTemplate $template) => [
                'id' => $template->getKey(),
                'name' => $template->name,
            ])->all();

        $customer = $worldwideQuote->opportunity->primaryAccount;

        return new WorldwideQuoteToSalesOrderData([
            'worldwide_quote_id' => $worldwideQuote->getKey(),
            'worldwide_quote_number' => $worldwideQuote->quote_number,
            'vat_number' => \transform($customer, static fn (Company $company) => $company->vat) ?? '',
            'vat_type' => \transform($customer, static fn (Company $company) => $company->vat_type) ?? VAT::VAT_NUMBER,
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
            throw new \RuntimeException('Quote must have a Template to create an export data.');
        }

        $outputCurrency = $this->getQuoteOutputCurrency($worldwideQuote);

        $packAssets = $this->getPackQuoteAssetsData($worldwideQuote, $outputCurrency);

        $packAssetNotes = implode("\n", $this->resolveNotesForAssets($worldwideQuote, $packAssets)->all());

        return new WorldwideQuotePreviewData([
            'template_data' => $this->getTemplateData($worldwideQuote),
            'distributions' => $this->getContractQuoteDistributionsData($worldwideQuote, $outputCurrency),
            'pack_assets' => $packAssets,
            'pack_asset_fields' => $this->getPackAssetFields($worldwideQuote,
                $worldwideQuote->activeVersion->quoteTemplate),
            'asset_notes' => $packAssetNotes,
            'pack_assets_are_grouped' => (bool) $worldwideQuote->activeVersion->use_groups,
            'quote_summary' => $this->getQuoteSummary($worldwideQuote, $outputCurrency),
            'contract_type_name' => $worldwideQuote->contractType->type_short_name,
        ]);
    }

    private function getQuoteOutputCurrency(WorldwideQuote $worldwideQuote): Currency
    {
        $currency = \with($worldwideQuote->activeVersion, static function (WorldwideQuoteVersion $version) {
            if (is_null($version->output_currency_id)) {
                return $version->quoteCurrency;
            }

            return $version->outputCurrency;
        });

        return \tap($currency, function (Currency $currency) use ($worldwideQuote): void {
            $currency->exchange_rate_value = $this->exchangeRateService->getTargetRate(
                $worldwideQuote->activeVersion->quoteCurrency,
                $worldwideQuote->activeVersion->outputCurrency
            );
        });
    }

    public function getTemplateData(WorldwideQuote $quote, bool $useLocalAssets = false): TemplateData
    {
        $tpl = $quote->activeVersion->quoteTemplate;
        $templateSchema = $tpl->form_data;

        return \tap(new TemplateData([
            'first_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['first_page'] ?? []),
            'assets_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['data_pages'] ?? []),
            'payment_schedule_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['payment_schedule'] ?? []),
            'last_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['last_page'] ?? []),
            'template_assets' => $this->getTemplateAssets($quote, $useLocalAssets),
            'headers' => $tpl->data_headers->pluck('value', 'key')->all(),
        ]), static function (TemplateData $templateData): void {
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
     * @return \App\Domain\Template\DataTransferObjects\TemplateElement[]
     */
    private function templatePageSchemaToArrayOfTemplateElement(array $pageSchema): array
    {
        return array_map(static function (array $element) {
            return new TemplateElement([
                'children' => $element['child'] ?? [],
                'class' => $element['class'] ?? '',
                'css' => $element['css'] ?? '',
                'toggle' => filter_var($element['toggle'] ?? false, FILTER_VALIDATE_BOOL),
                'visibility' => filter_var($element['visibility'] ?? false, FILTER_VALIDATE_BOOL),
            ]);
        }, $pageSchema);
    }

    private function getTemplateAssets(WorldwideQuote $quote, bool $useLocalAssets = false): TemplateAssets
    {
        $company = $quote->activeVersion->company;

        $flags = ThumbHelper::MAP;

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

        $companyImages = \transform($company->image, static function (Image $image) use ($flags, $company) {
            return ThumbHelper::getLogoDimensionsFromImage(
                $image,
                $company->thumbnailProperties(),
                'company_',
                $flags
            );
        }, []);

        $templateAssets = array_merge($templateAssets, $companyImages);

        /** @var Collection<Vendor>|\App\Domain\Vendor\Models\Vendor[] $vendors */
        $vendors = \with($quote, static function (WorldwideQuote $quote) {
            if ($quote->contract_type_id === \CT_PACK) {
                return Vendor::query()
                    ->whereIn((new Vendor())->getQualifiedKeyName(), static function (BaseBuilder $builder) use ($quote) {
                        return $builder->selectRaw('distinct(vendor_id)')
                            ->from('worldwide_quote_assets')
                            ->where($quote->activeVersion->assets()->getQualifiedForeignKeyName(),
                                $quote->activeVersion->getKey())
                            ->whereNotNull('vendor_id')
                            ->where('is_selected', true);
                    })
                    ->has('image')
                    ->get();
            }

            if ($quote->contract_type_id === \CT_CONTRACT) {
                $vendors = $quote->activeVersion->worldwideDistributions
                    ->lazy()
                    ->pluck('vendors')
                    ->collapse()
                    ->filter(static function (Vendor $vendor): bool {
                        return $vendor->image !== null;
                    })
                    ->values()
                    ->collect();

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

        $composeLogoSet = static function (array $logoSet) {
            static $whitespaceImg = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAfSURBVHgB7cqxAQAADAEw7f8/4wSDUeYcDYFHaLETBWmaBBDqHm1tAAAAAElFTkSuQmCC';

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
     * @return WorldwideDistributionData[]
     */
    public function getContractQuoteDistributionsData(WorldwideQuote $worldwideQuote, Currency $outputCurrency): array
    {
        if ($worldwideQuote->contract_type_id !== \CT_CONTRACT) {
            return [];
        }

        $paymentScheduleFields = $this->getPaymentScheduleFields($worldwideQuote->activeVersion->quoteTemplate);

        return $worldwideQuote->activeVersion->worldwideDistributions->map(function (WorldwideDistribution $distribution
        ) use (
            $paymentScheduleFields,
            $worldwideQuote,
            $outputCurrency
        ) {
            if (is_null($distribution->total_price)) {
                $distribution->total_price = $this->worldwideDistributionCalc->calculateTotalPriceOfDistributorQuote($distribution);
            }

            if (is_null($distribution->final_total_price)) {
                $distributionFinalTotalPrice = $this->worldwideDistributionCalc->calculateDistributionFinalTotalPrice($distribution,
                    (float) $distribution->total_price);

                $distribution->final_total_price = $distributionFinalTotalPrice->final_total_price_value;
            }

            $priceSummaryOfDistributorQuote = $this->worldwideDistributionCalc->calculatePriceSummaryOfDistributorQuote($distribution);

            $priceValueCoeff = \with($priceSummaryOfDistributorQuote,
                static function (ImmutablePriceSummaryData $priceSummaryData): float {
                    if ($priceSummaryData->total_price !== 0.0) {
                        return $priceSummaryData->final_total_price_excluding_tax / $priceSummaryData->total_price;
                    }

                    return 0.0;
                });

            $assetsData = $this->getAssetsDataOfDistributorQuote($distribution, $priceValueCoeff, $outputCurrency);

            $assetFields = $this->getAssetFieldsOfDistributorQuote($distribution,
                $worldwideQuote->activeVersion->quoteTemplate);

            $mappedFieldsCount = count($assetFields);

            $paymentScheduleData = \with($distribution->scheduleFile,
                function (?QuoteFile $scheduleFile) use ($priceValueCoeff, $outputCurrency) {
                    if (is_null($scheduleFile)) {
                        return [];
                    }

                    if (is_null($scheduleFile->scheduleData)) {
                        return [];
                    }

                    return $this->paymentScheduleToArrayOfPaymentData(BaseCollection::unwrap($scheduleFile->scheduleData->value),
                        $priceValueCoeff, $outputCurrency);
                });

            /** @var \App\Domain\Company\Models\Company $customer */
            $opportunity = $worldwideQuote->opportunity;
            $customer = $opportunity->primaryAccount;

            $serviceLevels = implode(', ', $customer->service_levels ?? []); // TODO: add the service levels.

            /** @var Address|null $quoteHardwareAddress */
            $quoteHardwareAddress = $distribution->addresses
                ->sortByDesc('pivot.is_default')
                ->first(static fn (Address $address) => in_array($address->address_type,
                    [AddressType::MACHINE, AddressType::HARDWARE, AddressType::EQUIPMENT], true));

            /** @var Address|null $quoteSoftwareAddress */
            $quoteSoftwareAddress = $distribution->addresses
                ->sortByDesc('pivot.is_default')
                ->first(static fn (Address $address) => $address->address_type === AddressType::SOFTWARE);

            /** @var \App\Domain\Contact\Models\Contact|null $quoteHardwareContact */
            $quoteHardwareContact = $distribution->contacts
                ->sortByDesc('pivot.is_default')
                ->first(static fn (Contact $contact) => $contact->contact_type === ContactType::HARDWARE);

            /** @var \App\Domain\Contact\Models\Contact|null $quoteSoftwareContact */
            $quoteSoftwareContact = $distribution->contacts
                ->sortByDesc('pivot.is_default')
                ->first(static fn (Contact $contact) => $contact->contact_type === ContactType::SOFTWARE);

            $assetNotes = implode("\n", $this->resolveNotesForAssets($worldwideQuote, $assetsData)->all());

            $contractDuration = self::formatOpportunityContractDuration($opportunity);

            return new WorldwideDistributionData([
                'supplier' => [
                    'supplier_name' => (string) $distribution->opportunitySupplier->supplier_name,
                    'contact_name' => (string) $distribution->opportunitySupplier->contact_name,
                    'contact_email' => (string) $distribution->opportunitySupplier->contact_email,
                    'country_name' => (string) $distribution->opportunitySupplier->country_name,
                ],

                'vendors' => $distribution->vendors->pluck('name')->join(', '),
                'country' => $distribution->country->name,
                'assets_data' => $assetsData,
                'mapped_fields_count' => (int) $mappedFieldsCount,
                'assets_are_grouped' => (bool) $distribution->use_groups,
                'asset_fields' => $assetFields,
                'asset_notes' => $assetNotes,

                'payment_schedule_fields' => $paymentScheduleFields,
                'payment_schedule_data' => $paymentScheduleData,
                'has_payment_schedule_data' => !empty($paymentScheduleData),

                'equipment_address' => self::formatAddressToString($quoteHardwareAddress),
                'hardware_phone' => $quoteHardwareContact->phone ?? ND_02,
                'hardware_contact' => \transform($quoteHardwareContact, static function (Contact $contact) {
                    return implode(' ', [$contact->first_name, $contact->last_name]);
                }) ?? ND_02,

                'software_address' => self::formatAddressToString($quoteSoftwareAddress),
                'software_phone' => $quoteSoftwareContact->phone ?? ND_02,
                'software_contact' => \transform($quoteSoftwareContact, static function (Contact $contact) {
                    return implode(' ', [$contact->first_name, $contact->last_name]);
                }) ?? ND_02,

                'service_levels' => $serviceLevels,
                'coverage_period' => $this->formatCoveragePeriod($opportunity),
                'coverage_period_from' => $this->formatter->format('date', $opportunity->opportunity_start_date),
                'coverage_period_to' => $this->formatter->format('date', $opportunity->opportunity_end_date),
                'contract_duration' => $contractDuration,
                'is_contract_duration_checked' => (bool) $opportunity->is_contract_duration_checked,

                'additional_details' => $distribution->additional_details ?? '',
                'pricing_document' => $distribution->pricing_document ?? '',
                'service_agreement_id' => $distribution->service_agreement_id ?? '',
                'system_handle' => $distribution->system_handle ?? '',
                'purchase_order_number' => $distribution->purchase_order_number ?? '',
                'vat_number' => $distribution->vat_number ?? '',
            ]);
        })->all();
    }

    private static function applyToAssetCollection(
        \Closure $closure,
        AssetData|AssetsGroupData ...$assetCollection
    ): void {
        foreach (self::iterateAssetCollection(...$assetCollection) as $asset) {
            $closure($asset);
        }
    }

    private static function iterateAssetCollection(AssetData|AssetsGroupData ...$assetCollection): \Generator
    {
        foreach ($assetCollection as $assetData) {
            if ($assetData instanceof AssetsGroupData) {
                foreach ($assetData->assets as $asset) {
                    yield $asset;
                }
            } else {
                yield $assetData;
            }
        }
    }

    public static function formatOpportunityContractDuration(Opportunity $opportunity): string
    {
        if (is_null($opportunity->contract_duration_months)) {
            return '';
        }

        return CarbonInterval::months($opportunity->contract_duration_months)->cascade()->forHumans();
    }

    public function formatCoveragePeriod(Opportunity $opportunity): string
    {
        if (is_null($opportunity->opportunity_start_date) || is_null($opportunity->opportunity_end_date)) {
            return '';
        }

        $startDateAssumedChar = $opportunity->is_opportunity_start_date_assumed ? '*' : '';
        $endDateAssumedChar = $opportunity->is_opportunity_end_date_assumed ? '*' : '';

        return implode('&nbsp;', [
            $this->formatter->format('date', $opportunity->opportunity_start_date).$startDateAssumedChar,
            'to',
            $this->formatter->format('date', $opportunity->opportunity_end_date).$endDateAssumedChar,
        ]);
    }

    /**
     * @return PaymentScheduleField[]
     */
    private function getPaymentScheduleFields(QuoteTemplate $quoteTemplate): array
    {
        $fields = static::getClassPublicProperties(PaymentData::class);

        $defaultHeaders = ['from' => 'From Date', 'to' => 'To Date', 'price' => 'Price'];

        return array_map(static function (string $fieldName) use ($defaultHeaders, $quoteTemplate) {
            $fieldHeader = \with($fieldName, static function (string $fieldName) use ($defaultHeaders, $quoteTemplate) {
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
            if ($property->isStatic()) {
                continue;
            }

            $fields[] = $property->getName();
        }

        return $fields;
    }

    /**
     * @return AssetData[]|AssetsGroupData[]
     */
    private function getAssetsDataOfDistributorQuote(
        WorldwideDistribution $distribution,
        float $priceValueCoeff,
        Currency $outputCurrency
    ): array {
        if (!$distribution->use_groups) {
            $distribution->load([
                'mappedRows' => static function (Relation $builder): void {
                    $builder->where('is_selected', true);
                },
            ]);

            $this->sortWorldwideDistributionRows($distribution, true);

            return \tap($this->mappedRowsToArrayOfAssetData(
                rows: $distribution->mappedRows,
                priceValueCoeff: $priceValueCoeff,
                outputCurrency: $outputCurrency,
                country: $distribution->country
            ), static function (array $assetData) use ($distribution): void {
                $contractDuration = self::formatOpportunityContractDuration($distribution->worldwideQuote->worldwideQuote->opportunity);

                /** @var Address|null $defaultInvoiceAddressOfEndUser */
                $defaultInvoiceAddressOfEndUser = $distribution->worldwideQuote->worldwideQuote->opportunity->endUser?->addresses()
                    ->wherePivot('is_default', true)
                    ->first();

                $vendorCode = $distribution->vendors->first()?->short_code ?? '';
                $countryOfDistributorQuote = $distribution->country;

                self::applyToAssetCollection(static function (AssetData $asset) use (
                    $vendorCode,
                    $contractDuration,
                    $defaultInvoiceAddressOfEndUser,
                    $countryOfDistributorQuote
                ): void {
                    $asset->contract_duration = $contractDuration;

                    $asset->end_user_invoice_country_code = $defaultInvoiceAddressOfEndUser?->country?->iso_3166_2 ?? '';
                    $asset->end_user_invoice_state = $defaultInvoiceAddressOfEndUser?->state ?? '';

                    $asset->vendor_short_code = $vendorCode;

                    if (blank($asset->country_code)) {
                        $asset->country_code = $countryOfDistributorQuote?->iso_3166_2 ?? '';
                    }
                }, ...$assetData);
            });
        }

        $distribution->load([
            'rowsGroups' => static function (Relation $builder): void {
                $builder->where('is_selected', true);
            }, 'rowsGroups.rows',
        ]);

        $this->sortWorldwideDistributionRowsGroups($distribution, true);

        return $distribution->rowsGroups->map(function (DistributionRowsGroup $rowsGroup) use (
            $priceValueCoeff,
            $outputCurrency,
            $distribution
        ) {
            $assets = \tap($this->mappedRowsToArrayOfAssetData(
                rows: $rowsGroup->rows,
                priceValueCoeff: $priceValueCoeff,
                outputCurrency: $outputCurrency,
                country: $distribution->country
            ), static function (array $assetData) use ($distribution): void {
                $contractDuration = self::formatOpportunityContractDuration($distribution->worldwideQuote->worldwideQuote->opportunity);

                /** @var Address|null $defaultInvoiceAddressOfEndUser */
                $defaultInvoiceAddressOfEndUser = $distribution->worldwideQuote->worldwideQuote->opportunity->endUser?->addresses()
                    ->wherePivot('is_default', true)
                    ->first();

                $vendorCode = $distribution->vendors->first()?->short_code ?? '';

                self::applyToAssetCollection(static function (AssetData $asset) use (
                    $vendorCode,
                    $contractDuration,
                    $defaultInvoiceAddressOfEndUser
                ): void {
                    $asset->contract_duration = $contractDuration;

                    $asset->end_user_invoice_country_code = $defaultInvoiceAddressOfEndUser?->country?->iso_3166_2 ?? '';
                    $asset->end_user_invoice_state = $defaultInvoiceAddressOfEndUser?->state ?? '';

                    $asset->vendor_short_code = $vendorCode;
                }, ...$assetData);
            });

            $groupTotalPrice = (float) $rowsGroup->rows_sum * $priceValueCoeff * (float) $outputCurrency->exchange_rate_value;

            return new AssetsGroupData([
                'group_name' => $rowsGroup->group_name,
                'assets' => $assets,
                'group_total_price' => $this->formatter->format('number', $groupTotalPrice,
                    prepend: $outputCurrency->symbol),
                'group_total_price_float' => $groupTotalPrice,
            ]);
        })->all();
    }

    public function sortWorldwideDistributionRows(WorldwideDistribution $distribution, bool $forPreview = false): void
    {
        $results = $distribution->mappedRows;

        if (filled($distribution->sort_rows_column)) {
            $results = $results->sortBy(
                callback: static function (MappedRow $row) use ($distribution) {
                    if ($distribution->sort_rows_column === 'machine_address') {
                        return self::formatAddressToString($row->machineAddress);
                    }

                    return $row->{$distribution->sort_rows_column};
                },
                options: SORT_NATURAL,
                descending: $distribution->sort_rows_direction === 'desc'
            )
                ->values();
        }

        if ($forPreview) {
            $results = Collection::make($results->groupBy('serial_no')->collapse()->all());
        }

        $distribution->setRelation('mappedRows', $results);
    }

    /**
     * @param Collection<\App\Domain\DocumentMapping\Models\MappedRow> $rows
     *
     * @return AssetData[]
     */
    private function mappedRowsToArrayOfAssetData(
        Collection $rows,
        float $priceValueCoeff,
        Currency $outputCurrency,
        Country $country
    ): array {
        return $rows->map(function (MappedRow $row) use ($priceValueCoeff, $outputCurrency) {
            $assetPrice = (float) $row->price * $priceValueCoeff * (float) $outputCurrency->exchange_rate_value;

            return new AssetData([
                'buy_currency_code' => '',
                'vendor_short_code' => '',
                'product_no' => $row->product_no ?? '',
                'service_sku' => $row->service_sku ?? '',
                'description' => $row->description ?? '',
                'serial_no' => (string) $row->serial_no ?? '',
                'is_serial_number_generated' => (bool) $row->is_serial_number_generated,
                'date_from' => $this->formatter->format('date', $row->date_from),
                'date_to' => $this->formatter->format('date', $row->date_to),
                'qty' => $row->qty ?? 1,
                'price' => $this->formatter->format('number', $assetPrice, prepend: $outputCurrency->symbol),
                'price_float' => (float) $row->price * $priceValueCoeff * (float) $outputCurrency->exchange_rate_value,
                'country_code' => $row->machineAddress?->country?->iso_3166_2 ?? '',
                'state' => $row->machineAddress?->state ?? '',
                'machine_address_string' => self::formatAddressToString($row->machineAddress),
                'pricing_document' => $row->pricing_document ?? '',
                'system_handle' => $row->system_handle ?? '',
                'searchable' => $row->searchable ?? '',
                'service_level_description' => $row->service_level_description ?? '',
            ]);
        })->all();
    }

    public function sortWorldwideDistributionRowsGroups(
        WorldwideDistribution $distribution,
        bool $forPreview = false
    ): void {
        $sortColumn = (string) $distribution->sort_rows_groups_column;

        $results = $distribution->rowsGroups
            ->when(filled($sortColumn), static function (Collection $groupsOfRows) use ($distribution): Collection {
                return $groupsOfRows->sortBy(
                    $distribution->sort_rows_groups_column,
                    SORT_NATURAL,
                    $distribution->sort_rows_groups_direction === 'desc'
                );
            })
            ->when($forPreview, static function (Collection $groupsOfRows): Collection {
                foreach ($groupsOfRows as $group) {
                    /* @var \App\Domain\Worldwide\Models\DistributionRowsGroup $group */
                    $group->setRelation('rows',
                        Collection::make($group->rows->groupBy('serial_no')->collapse()->values()->all()));
                }

                return $groupsOfRows;
            })
            ->values();

        $distribution->setRelation('rowsGroups', $results);
    }

    private function getAssetFieldsOfDistributorQuote(
        WorldwideDistribution $distribution,
        QuoteTemplate $quoteTemplate
    ): array {
        $assetFields = [];

        $mapping = $distribution->mapping->keyBy('template_field_name')
            ->put('contract_duration', \tap(new DistributionFieldColumn(), static function (DistributionFieldColumn $column): void {
                $column->setAttribute('template_field_name', 'contract_duration');
                $column->setAttribute('is_editable', true);
            }))
            ->put('machine_address', \tap(new DistributionFieldColumn(), static function (DistributionFieldColumn $column): void {
                $column->setAttribute('template_field_name', 'machine_address_string');
                $column->setAttribute('is_editable', true);
            }));

        $dateTemplateFieldNames = \value(static function () use ($distribution): array {
            if ($distribution->worldwideQuote->worldwideQuote->opportunity->is_contract_duration_checked) {
                return ['contract_duration'];
            }

            return ['date_from', 'date_to'];
        });

        $templateFieldNames = [
            'machine_address',
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
            /** @var \App\Domain\Worldwide\Models\DistributionFieldColumn $column */
            $column = $mapping[$fieldName] ?? null;

            if (is_null($column)) {
                continue;
            }

            if (is_null($column->importable_column_id) && !$column->is_editable) {
                continue;
            }

            $assetFields[] = new AssetField([
                'field_name' => $column->template_field_name,
                'field_header' => self::resolveTemplateDataHeader($column->template_field_name,
                    $quoteTemplate) ?? $column->template_field_name,
            ]);
        }

        return $assetFields;
    }

    /**
     * @return PaymentData[]
     */
    private function paymentScheduleToArrayOfPaymentData(
        array $value,
        float $priceValueCoeff,
        Currency $outputCurrency
    ): array {
        return array_map(function (array $payment) use ($priceValueCoeff, $outputCurrency) {
            $paymentValue = (float) PriceParser::parseAmount($payment['price'] ?? '') * $priceValueCoeff * (float) $outputCurrency->exchange_rate_value;

            return new PaymentData([
                'from' => $payment['from'],
                'to' => $payment['to'],
                'price' => $this->formatter->format('number', $paymentValue, prepend: $outputCurrency->symbol),
            ]);
        }, $value);
    }

    public static function formatAddressToString(?Address $address): string
    {
        if (is_null($address)) {
            return ND_02;
        }

        return implode(', ', array_filter([
            $address->address_1, $address->address_2, $address->city, $address->country?->iso_3166_2,
            $address->post_code,
        ]));
    }

    public function getPackQuoteAssetsData(WorldwideQuote $quote, Currency $outputCurrency): array
    {
        if ($quote->contract_type_id !== \CT_PACK) {
            return [];
        }

        $quotePriceData = $this->getQuotePriceData($quote);

        $activeVersionOfQuote = $quote->activeVersion;

        if (!$activeVersionOfQuote->use_groups) {
            $activeVersionOfQuote->load([
                'assets' => static function (Relation $builder): void {
                    $builder->where('is_selected', true);
                },
            ]);

            $this->sortWorldwidePackQuoteAssets($quote, true);

            foreach ($activeVersionOfQuote->assets as $asset) {
                $asset->setAttribute('date_to', $quote->opportunity->opportunity_end_date);
            }

            return \tap($this->worldwideQuoteAssetsToArrayOfAssetData(
                assets: $activeVersionOfQuote->assets,
                priceValueCoeff: $quotePriceData->price_value_coefficient,
                outputCurrency: $outputCurrency,
            ), static function (array $assetsData) use ($quote): void {
                $contractDuration = self::formatOpportunityContractDuration($quote->opportunity);

                /** @var \App\Domain\Address\Models\Address|null $defaultInvoiceAddressOfEndUser */
                $defaultInvoiceAddressOfEndUser = $quote->opportunity->endUser?->addresses()
                    ->wherePivot('is_default', true)
                    ->first();

                self::applyToAssetCollection(static function (AssetData $asset) use (
                    $contractDuration,
                    $defaultInvoiceAddressOfEndUser
                ): void {
                    $asset->contract_duration = $contractDuration;
                    $asset->end_user_invoice_country_code = $defaultInvoiceAddressOfEndUser?->country?->iso_3166_2 ?? '';
                    $asset->end_user_invoice_state = $defaultInvoiceAddressOfEndUser?->state ?? '';
                }, ...$assetsData);
            });
        }

        $quote->activeVersion->load([
            'assetsGroups' => static function (Relation $builder): void {
                $builder->where('is_selected', true);
            }, 'assetsGroups.assets', 'assetsGroups.assets.vendor:id,short_code',
        ]);

        $this->sortGroupsOfPackAssets($quote, true);

        return $quote->activeVersion->assetsGroups->map(function (WorldwideQuoteAssetsGroup $assetsGroup) use (
            $quote,
            $outputCurrency,
            $quotePriceData
        ) {
            foreach ($assetsGroup->assets as $asset) {
                $asset->setAttribute('vendor_short_code', $asset->vendor?->short_code);
                $asset->setAttribute('date_to', $quote->opportunity->opportunity_end_date);
            }

            $assets = \tap($this->worldwideQuoteAssetsToArrayOfAssetData(
                assets: $assetsGroup->assets,
                priceValueCoeff: $quotePriceData->price_value_coefficient,
                outputCurrency: $outputCurrency,
            ), static function (array $assetsData) use ($quote): void {
                $contractDuration = self::formatOpportunityContractDuration($quote->opportunity);

                /** @var \App\Domain\Address\Models\Address|null $defaultInvoiceAddressOfEndUser */
                $defaultInvoiceAddressOfEndUser = $quote->opportunity->endUser?->addresses()
                    ->wherePivot('is_default', true)
                    ->first();

                self::applyToAssetCollection(static function (AssetData $asset) use (
                    $contractDuration,
                    $defaultInvoiceAddressOfEndUser
                ): void {
                    $asset->contract_duration = $contractDuration;
                    $asset->end_user_invoice_country_code = $defaultInvoiceAddressOfEndUser?->country?->iso_3166_2 ?? '';
                    $asset->end_user_invoice_state = $defaultInvoiceAddressOfEndUser?->state ?? '';
                }, ...$assetsData);
            });

            $groupTotalPrice = (float) $assetsGroup->assets_sum_price * $quotePriceData->price_value_coefficient * (float) $outputCurrency->exchange_rate_value;

            return new AssetsGroupData([
                'group_name' => $assetsGroup->group_name,
                'assets' => $assets,
                'group_total_price' => $this->formatter->format('number', $groupTotalPrice,
                    prepend: $outputCurrency->symbol),
                'group_total_price_float' => $groupTotalPrice,
            ]);
        })->all();
    }

    private function getQuotePriceData(WorldwideQuote $worldwideQuote): QuotePriceData
    {
        return \tap(new QuotePriceData(), function (QuotePriceData $quotePriceData) use ($worldwideQuote): void {
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

    public function sortWorldwidePackQuoteAssets(WorldwideQuote $quote, bool $forPreview = false): void
    {
        $activeVersion = $quote->activeVersion;

        $sortRowsColumn = $activeVersion->sort_rows_column;

        $results = $activeVersion->assets;

        if (filled($sortRowsColumn)) {
            $results = $activeVersion->assets
                ->sortBy(
                    static function (WorldwideQuoteAsset $asset) use ($sortRowsColumn) {
                        if ($sortRowsColumn === 'machine_address') {
                            return self::formatAddressToString($asset->machineAddress);
                        }

                        return $asset->{$sortRowsColumn};
                    },
                    SORT_NATURAL,
                    $activeVersion->sort_rows_direction === 'desc'
                )
                ->values();
        }

        if ($forPreview) {
            $results = Collection::make($results->groupBy('serial_no')->collapse()->all());
        }

        $activeVersion->setRelation('assets', $results);
    }

    public function markExclusivityOfWorldwidePackQuoteAssetsForCustomer(
        WorldwideQuote $quote,
        Collection|WorldwideQuoteAsset $assets
    ): void {
        if ($assets instanceof WorldwideQuoteAsset) {
            $assets = new Collection([$assets]);
        }

        $primaryAccount = $quote->opportunity->primaryAccount;

        if (is_null($primaryAccount)) {
            return;
        }

        $hashGenericAssetKey = static function (Asset $asset) {
            return sprintf('%s.%s.%s', $asset->serial_number, $asset->product_number, $asset->service_description);
        };

        $hashAssetKey = static function (WorldwideQuoteAsset $asset) {
            return sprintf('%s.%s.%s', $asset->serial_no, $asset->sku, $asset->service_level_description);
        };

        $sameGenericAssetDict = $this->assetQueries->sameGenericAssetsQuery($assets)
            ->whereDoesntHave('companies', static function (Builder $builder) use ($primaryAccount): void {
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

    public function markExclusivityOfWorldwideDistributionRowsForCustomer(
        WorldwideDistribution $distributorQuote,
        Collection|MappedRow $rows
    ): void {
        if ($rows instanceof MappedRow) {
            $rows = new Collection([$rows]);
        }

        $primaryAccount = $distributorQuote->worldwideQuote->worldwideQuote->opportunity->primaryAccount;

        if (is_null($primaryAccount)) {
            return;
        }

        $hashMappedRowKey = static function (MappedRow $mappedRow) {
            return sprintf('%s.%s.%s', $mappedRow->serial_no, $mappedRow->product_no,
                $mappedRow->service_level_description);
        };

        $hashGenericAssetKey = static function (Asset $asset) {
            return sprintf('%s.%s.%s', $asset->serial_number, $asset->product_number, $asset->service_description);
        };

        $sameGenericAssetDict = $this->mappedRowQueries->sameGenericAssetsQuery($rows)
            ->whereDoesntHave('companies', static function (Builder $builder) use ($primaryAccount): void {
                $builder->whereKey($primaryAccount);
            })
            ->with('companies:companies.id,companies.user_id,companies.name')
            ->get()
            ->keyBy($hashGenericAssetKey);

        foreach ($rows as $row) {
            /** @var \App\Domain\DocumentMapping\Models\MappedRow $row */
            $rowKeyHash = $hashMappedRowKey($row);

            /** @var Asset|null $sameGenericAsset */
            $sameGenericAsset = $sameGenericAssetDict->get($rowKeyHash);

            $row->setAttribute('is_customer_exclusive_asset', is_null($sameGenericAsset));

            if (!is_null($sameGenericAsset)) {
                $row->setAttribute('owned_by_customer', $sameGenericAsset->companies->first());
            }
        }
    }

    public function includeContractDurationToPackAssets(
        WorldwideQuote $quote,
        Collection|WorldwideQuoteAsset $assets
    ): void {
        if ($assets instanceof WorldwideQuoteAsset) {
            $assets = new Collection([$assets]);
        }

        /** @var \App\Domain\Worldwide\Models\WorldwideQuoteAsset[] $assets */
        $attributes = [
            'contract_duration' => self::formatOpportunityContractDuration($quote->opportunity),
            'contract_duration_months' => $quote->opportunity->contract_duration_months,
        ];

        foreach ($assets as $asset) {
            foreach ($attributes as $attribute => $value) {
                $asset->setAttribute($attribute, $value);
            }
        }
    }

    public function includeContractDurationToContractAssets(
        WorldwideDistribution $distributorQuote,
        Collection|MappedRow $rows
    ): void {
        if ($rows instanceof MappedRow) {
            $rows = new Collection([$rows]);
        }

        /** @var MappedRow[] $rows */
        $attributes = [
            'contract_duration' => self::formatOpportunityContractDuration($distributorQuote->worldwideQuote->worldwideQuote->opportunity),
            'contract_duration_months' => $distributorQuote->worldwideQuote->worldwideQuote->opportunity->contract_duration_months,
        ];

        foreach ($rows as $row) {
            foreach ($attributes as $attribute => $value) {
                $row->setAttribute($attribute, $value);
            }
        }
    }

    private function worldwideQuoteAssetsToArrayOfAssetData(
        Collection $assets,
        float $priceValueCoeff,
        Currency $outputCurrency
    ): array {
        return $assets
            ->load('machineAddress.country')
            ->map(function (WorldwideQuoteAsset $asset) use ($outputCurrency, $priceValueCoeff) {
                $assetFloatPrice = (float) $asset->price * $priceValueCoeff * (float) $outputCurrency->exchange_rate_value;

                return new AssetData([
                    'buy_currency_code' => $asset->buyCurrency?->code ?? '',
                    'vendor_short_code' => $asset->vendor_short_code ?? '',
                    'product_no' => $asset->sku ?? '',
                    'service_sku' => $asset->service_sku ?? '',
                    'description' => $asset->product_name ?? '',
                    'serial_no' => (string) $asset->serial_no ?? '',
                    'is_serial_number_generated' => (bool) $asset->is_serial_number_generated,
                    'date_from' => $this->formatter->format('date', $asset->expiry_date),
                    'date_to' => $this->formatter->format('date', $asset->date_to),
                    'qty' => 1,
                    'price' => $this->formatter->format('number', $assetFloatPrice, prepend: $outputCurrency->symbol),
                    'price_float' => $assetFloatPrice,
                    'country_code' => $asset->machineAddress?->country?->iso_3166_2 ?? '',
                    'state' => $asset->machineAddress?->state ?? '',
                    'machine_address_string' => self::formatAddressToString($asset->machineAddress),
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

        $assetDateFields = \value(static function () use ($quote): array {
            if ($quote->opportunity->is_contract_duration_checked) {
                return ['contract_duration'];
            }

            return ['date_from', 'date_to'];
        });

        $assetFieldNames = [
            'machine_address_string',
            'vendor_short_code',
            'product_no',
            'description',
            'serial_no',
            'service_level_description',
            'service_sku',
            ...$assetDateFields,
            'price',
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

    private function resolveNotesForAssets(WorldwideQuote $quote, array $assets): MessageBag
    {
        $quoteTemplate = $quote->activeVersion->quoteTemplate;

        $notes = (new MessageBag())->setFormat(':key :message');

        $assetFlags = (new AssetFlagResolver(...$assets))();

        $resolveMessageKey = static function (\Countable $bag): string {
            return str_repeat('*', $bag->count() + 1);
        };

        $supportDatesAssumed = $quote->opportunity->is_opportunity_start_date_assumed || $quote->opportunity->is_opportunity_end_date_assumed;

        if ($supportDatesAssumed) {
            $datesAssumedNote = self::resolveTemplateDataHeader('support_dates_assumed', $quoteTemplate) ?? '';

            $notes->add($resolveMessageKey($notes), $datesAssumedNote);
        }

        if ($assetFlags & AssetFlagResolver::GEN_SN) {
            $autoGeneratedSerialNote = self::resolveTemplateDataHeader('auto_generated_serial_number_text',
                $quoteTemplate) ?? '';

            $notes->add($messageKey = $resolveMessageKey($notes), $autoGeneratedSerialNote);

            foreach (static::iterateAssetCollection(...$assets) as $asset) {
                if ($asset->is_serial_number_generated) {
                    $asset->serial_no = $asset->serial_no." $messageKey";
                }
            }
        }

        if ($assetFlags & AssetFlagResolver::CA_LOC) {
            $canadaTaxNote = self::resolveTemplateDataHeader('canada_tax_text', $quoteTemplate) ?? '';

            $notes->add($resolveMessageKey($notes), $canadaTaxNote);
        }

        if ($assetFlags & AssetFlagResolver::US_LOC) {
            $usStatesNote = self::resolveTemplateDataHeader('ca_ct_hi_md_ma_nv_pr_ri_tn_states_tax_text',
                $quoteTemplate) ?? '';

            $notes->add($resolveMessageKey($notes), $usStatesNote);
        }

        return $notes;
    }

    private function isTaxAppliedToQuote(WorldwideQuote $quote): bool
    {
        return match ($quote->contract_type_id) {
            \CT_PACK => $quote->activeVersion->tax_value > 0,
            \CT_CONTRACT => $quote->activeVersion->worldwideDistributions->contains(static function (
                WorldwideDistribution $distributorQuote
            ) {
                return $distributorQuote->tax_value > 0;
            }),
        };
    }

    public function getQuoteSummary(WorldwideQuote $worldwideQuote, Currency $outputCurrency): QuoteSummary
    {
        $activeVersion = $worldwideQuote->activeVersion;

        $quoteDataAggregation = match ($worldwideQuote->contract_type_id) {
            \CT_CONTRACT => $this->getContractQuoteDataAggregation($worldwideQuote, $outputCurrency),
            \CT_PACK => $this->getPackQuoteDataAggregation($worldwideQuote, $outputCurrency),
        };

        $opportunity = $worldwideQuote->opportunity;

        /** @var Carbon|null $opportunityStartDate */
        $opportunityStartDate = \transform($opportunity->opportunity_start_date,
            static fn (string $date) => Carbon::createFromFormat('Y-m-d', $date));

        /** @var Carbon|null $opportunityEndDate */
        $opportunityEndDate = \transform($opportunity->opportunity_end_date,
            static fn (string $date) => Carbon::createFromFormat('Y-m-d', $date));

        /** @var Carbon|null $opportunityClosingDate */
        $opportunityClosingDate = \transform($opportunity->opportunity_closing_date,
            static fn (string $date) => Carbon::createFromFormat('Y-m-d', $date));

        /** @var Carbon|null $quoteExpiryDate */
        $quoteExpiryDate = \transform($activeVersion->quote_expiry_date,
            static fn (string $date) => Carbon::createFromFormat('Y-m-d', $date));

        $quotePriceData = $this->getQuotePriceData($worldwideQuote);

        $defaultPrimaryAccountAddresses = $opportunity->primaryAccount?->addresses()
            ?->wherePivot('is_default', true)
            ?->get() ?? collect();
        $defaultPrimaryAccountContacts = $opportunity->primaryAccount?->contacts()
            ?->wherePivot('is_default', true)
            ?->get() ?? collect();

        $defaultEndUserAddresses = $opportunity->endUser?->addresses()
            ?->wherePivot('is_default', true)
            ?->get() ?? collect();
        $defaultEndUserContacts = $opportunity->endUser?->contacts()
            ?->wherePivot('is_default', true)
            ?->get() ?? collect();

        /** @var \App\Domain\Address\Models\Address|null $quoteHardwareAddress */
        $quoteHardwareAddress = $defaultPrimaryAccountAddresses
            ->first(static fn (Address $address) => in_array($address->address_type,
                [AddressType::MACHINE, AddressType::HARDWARE, AddressType::EQUIPMENT], true));

        /* @var Address|null $quoteInvoiceAddress */
        if (null !== $worldwideQuote->submitted_at && null !== $activeVersion->submittedPaInvoiceAddress) {
            $quoteInvoiceAddress = $activeVersion->submittedPaInvoiceAddress;
        } else {
            $quoteInvoiceAddress = $defaultPrimaryAccountAddresses
                ->lazy()
                ->whereStrict('address_type', AddressType::INVOICE)
                ->first();
        }

        /** @var \App\Domain\Address\Models\Address|null $quoteSoftwareAddress */
        $quoteSoftwareAddress = $defaultPrimaryAccountAddresses
            ->first(static fn (Address $address) => $address->address_type === AddressType::SOFTWARE);

        /** @var \App\Domain\Contact\Models\Contact|null $quoteHardwareContact */
        $quoteHardwareContact = $defaultPrimaryAccountContacts
            ->first(static fn (Contact $contact) => $contact->contact_type === ContactType::HARDWARE);

        /** @var \App\Domain\Contact\Models\Contact|null $quoteSoftwareContact */
        $quoteSoftwareContact = $defaultPrimaryAccountContacts
            ->first(static fn (Contact $contact) => $contact->contact_type === ContactType::SOFTWARE);

        /** @var Address|null $endUserHardwareAddress */
        $endUserHardwareAddress = $defaultEndUserAddresses
            ->first(static fn (Address $address) => in_array($address->address_type,
                [AddressType::MACHINE, AddressType::HARDWARE, AddressType::EQUIPMENT], true));

        /** @var Address|null $endUserInvoiceAddress */
        $endUserInvoiceAddress = $defaultEndUserAddresses
            ->first(static fn (Address $address) => in_array($address->address_type, [AddressType::INVOICE], true));

        /** @var \App\Domain\Contact\Models\Contact|null $endUserHardwareContact */
        $endUserHardwareContact = $defaultEndUserContacts
            ->first(static fn (Contact $contact) => $contact->contact_type === ContactType::HARDWARE);

        $contactStringFormatter = static function (?Contact $contact): string {
            if (is_null($contact)) {
                return ND_02;
            }

            return implode(' ', [$contact->first_name, $contact->last_name]);
        };

        $footerNotes = [];

        return new QuoteSummary([
            'company_name' => $activeVersion->company->name,
            'customer_name' => $opportunity->primaryAccount->name,
            'quotation_number' => $worldwideQuote->quote_number,
            'export_file_name' => $worldwideQuote->quote_number,
            'sales_order_number' => (string) $worldwideQuote->salesOrder?->order_number,

            'service_levels' => '',
            'invoicing_terms' => '',
            'payment_terms' => $activeVersion->payment_terms ?? '',

            'support_start' => $this->formatter->format('date', $opportunityStartDate),
            'support_start_assumed_char' => $opportunity->is_opportunity_start_date_assumed ? '*' : '',
            'support_end' => $this->formatter->format('date', $opportunityEndDate),
            'support_end_assumed_char' => $opportunity->is_opportunity_end_date_assumed ? '*' : '',
            'valid_until' => $this->formatter->format('date', $quoteExpiryDate),

            'contract_duration' => (string) \transform($opportunity->contract_duration_months,
                static fn (int $months) => CarbonInterval::months($months)->cascade()->forHumans()),
            'is_contract_duration_checked' => (bool) $opportunity->is_contract_duration_checked,

            'list_price' => $this->formatter->format('number', $quotePriceData->total_price_value_after_margin,
                prepend: $outputCurrency->symbol),
            'applicable_discounts' => $this->formatter->format('number', $quotePriceData->applicable_discounts_value,
                prepend: $outputCurrency->symbol),
            'final_price' => $this->formatter->format('number', $quotePriceData->final_total_price_value_excluding_tax,
                prepend: $outputCurrency->symbol),

            'quote_price_value_coefficient' => $quotePriceData->price_value_coefficient,

            'contact_name' => $contactStringFormatter($opportunity->primaryAccountContact),
            'contact_email' => $opportunity->primaryAccountContact?->email ?? ND_02,
            'contact_phone' => $opportunity->primaryAccountContact?->phone ?? ND_02,
            'contact_country' => ($quoteInvoiceAddress ?? $quoteHardwareAddress)?->country?->iso_3166_2 ?? ND_02,

            'primary_account_inv_address_1' => $quoteInvoiceAddress?->address_1,
            'primary_account_inv_address_2' => $quoteInvoiceAddress?->address_2,
            'primary_account_inv_country' => $quoteInvoiceAddress?->country?->iso_3166_2,
            'primary_account_inv_state' => $quoteInvoiceAddress?->state,
            'primary_account_inv_state_code' => $quoteInvoiceAddress?->state_code,
            'primary_account_inv_post_code' => $quoteInvoiceAddress?->post_code,

            'end_user_name' => $opportunity->endUser?->name ?? ND_02,
            'end_user_contact_country' => collect($quoteDataAggregation)->map->country_code->unique()->implode(', '),
            'end_user_contact_name' => $activeVersion->are_end_user_contacts_available ? $contactStringFormatter($endUserHardwareContact) : ND_02,
            'end_user_contact_email' => $activeVersion->are_end_user_contacts_available ? ($endUserHardwareContact?->email ?? ND_02) : ND_02,
            'end_user_company_email' => $opportunity->endUser?->email ?? ND_02,
            'end_user_hw_post_code' => $endUserHardwareAddress?->post_code ?? ND_02,
            'end_user_inv_post_code' => $endUserInvoiceAddress?->post_code ?? ND_02,

            'account_manager_name' => $opportunity->accountManager?->user_fullname ?? ND_02,
            'account_manager_email' => $opportunity->accountManager?->email ?? ND_02,

            'quote_data_aggregation_fields' => $this->getQuoteDataAggregationFields($activeVersion->quoteTemplate),
            'quote_data_aggregation' => $quoteDataAggregation,

            'sub_total_value' => $this->formatter->format('number',
                $quotePriceData->final_total_price_value_excluding_tax, prepend: $outputCurrency->symbol),
            'total_value_including_tax' => $this->formatter->format('number', $quotePriceData->final_total_price_value,
                prepend: $outputCurrency->symbol),
            'grand_total_value' => $this->formatter->format('number', $quotePriceData->final_total_price_value,
                prepend: $outputCurrency->symbol),

            'equipment_address' => self::formatAddressToString($quoteHardwareAddress),
            'hardware_contact' => $contactStringFormatter($quoteHardwareContact),
            'hardware_phone' => $quoteHardwareContact?->phone ?? ND_02,
            'software_address' => self::formatAddressToString($quoteSoftwareAddress),
            'software_contact' => $contactStringFormatter($quoteSoftwareContact),
            'software_phone' => $quoteSoftwareContact?->phone ?? ND_02,
            'coverage_period' => $this->formatCoveragePeriod($opportunity),
            'coverage_period_from' => $this->formatter->format('date', $opportunityStartDate),
            'coverage_period_to' => $this->formatter->format('date', $opportunityEndDate),
            'additional_details' => $activeVersion->additional_details ?? '',
            'pricing_document' => $activeVersion->pricing_document ?? '',
            'service_agreement_id' => $activeVersion->service_agreement_id ?? '',
            'system_handle' => $activeVersion->system_handle ?? '',

            'footer_notes' => implode("\n", $footerNotes),
        ]);
    }

    private function getPackQuoteDataAggregation(WorldwideQuote $quote, Currency $outputCurrency): array
    {
        $duration = $this->resolveOpportunityDuration($quote->opportunity);

        $assetCollection = $this->getPackQuoteAssetsData($quote, $outputCurrency);

        $assets = collect(iterator_to_array($this->iterateAssetCollection(...$assetCollection)));

        return $assets->groupBy(static function (AssetData $asset) {
            return "$asset->country_code::$asset->vendor_short_code";
        })
            ->map(function (BaseCollection $group) use ($duration, $outputCurrency) {
                /** @var Vendor $vendor */
                $vendor = Vendor::query()->where('short_code', $group[0]->vendor_short_code)->first();
                /** @var Country $country */
                $country = Country::query()->where('iso_3166_2', $group[0]->country_code)->first();

                $totalPrice = $group->sum('price_float');

                return new QuoteAggregation([
                    'vendor_name' => $vendor?->name ?? '',
                    'country_name' => $country?->name ?? '',
                    'country_code' => $country?->iso_3166_2 ?? '',
                    'duration' => $duration,
                    'qty' => $group->count(),
                    'total_price' => $this->formatter->format('number', $totalPrice, prepend: $outputCurrency->symbol),
                ]);
            })
            ->values()
            ->all();
    }

    private function getContractQuoteDataAggregation(WorldwideQuote $quote, Currency $outputCurrency): array
    {
        $duration = $this->resolveOpportunityDuration($quote->opportunity);

        $assets = collect();

        foreach ($quote->activeVersion->worldwideDistributions as $worldwideDistribution) {
            /** @var WorldwideDistribution $worldwideDistribution */
            $priceSummaryOfDistributorQuote = $this->worldwideDistributionCalc->calculatePriceSummaryOfDistributorQuote($worldwideDistribution);

            $priceValueCoeff = \with($priceSummaryOfDistributorQuote,
                static function (ImmutablePriceSummaryData $priceSummaryData): float {
                    if ($priceSummaryData->total_price !== 0.0) {
                        return $priceSummaryData->final_total_price_excluding_tax / $priceSummaryData->total_price;
                    }

                    return 0.0;
                });

            $assetCollection = $this->getAssetsDataOfDistributorQuote($worldwideDistribution, $priceValueCoeff,
                $outputCurrency);

            $assetsOfDistributorQuote = collect(iterator_to_array($this->iterateAssetCollection(...$assetCollection)));

            $assets->push(...$assetsOfDistributorQuote);
        }

        return $assets->groupBy(static function (AssetData $asset) {
            return "$asset->country_code::$asset->vendor_short_code";
        })
            ->map(function (BaseCollection $group) use ($outputCurrency, $duration) {
                /** @var Vendor $vendor */
                $vendor = Vendor::query()->where('short_code', $group[0]->vendor_short_code)->first();
                /** @var Country $country */
                $country = Country::query()->where('iso_3166_2', $group[0]->country_code)->first();

                $totalPrice = $group->sum('price_float');

                return new QuoteAggregation([
                    'vendor_name' => $vendor?->name ?? '',
                    'country_name' => $country?->name ?? '',
                    'country_code' => $country?->iso_3166_2 ?? '',
                    'duration' => $duration,
                    'qty' => $group->count(),
                    'total_price' => $this->formatter->format('number', $totalPrice, prepend: $outputCurrency->symbol),
                ]);
            })
            ->values()
            ->all();
    }

    private function resolveOpportunityDuration(Opportunity $opportunity): string
    {
        if ($opportunity->is_contract_duration_checked) {
            return self::formatOpportunityContractDuration($opportunity);
        }

        if (is_null($opportunity->opportunity_start_date) || is_null($opportunity->opportunity_end_date)) {
            return '';
        }

        return Carbon::createFromFormat('Y-m-d', $opportunity->opportunity_start_date)
            ->longAbsoluteDiffForHumans(Carbon::createFromFormat('Y-m-d', $opportunity->opportunity_end_date)->addDay(),
                2);
    }

    /**
     * @return AggregationField[]
     */
    private function getQuoteDataAggregationFields(QuoteTemplate $quoteTemplate): array
    {
        $fields = static::getClassPublicProperties(QuoteAggregation::class);

        $fields = array_values(
            array_filter($fields, static fn (string $fieldName) => !in_array($fieldName, ['duration', 'country_code'], true))
        );

        return array_map(static function (string $fieldName) use ($quoteTemplate) {
            $fieldHeader = \with($fieldName, static function (string $fieldName) use ($quoteTemplate) {
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
            throw new \RuntimeException('Quote must have a Template to create an export data.');
        }

        $outputCurrency = $this->getQuoteOutputCurrency($worldwideQuote);

        $packAssets = $this->getPackQuoteAssetsData($worldwideQuote, $outputCurrency);

        $packAssetNotes = implode("\n", $this->resolveNotesForAssets($worldwideQuote, $packAssets)->all());

        return new WorldwideQuotePreviewData([
            'template_data' => $this->getTemplateData($worldwideQuote, true),
            'distributions' => $this->getContractQuoteDistributionsData($worldwideQuote, $outputCurrency),
            'pack_assets' => $packAssets,
            'pack_asset_fields' => $this->getPackAssetFields($worldwideQuote,
                $worldwideQuote->activeVersion->quoteTemplate),
            'asset_notes' => $packAssetNotes,
            'pack_assets_are_grouped' => (bool) $worldwideQuote->activeVersion->use_groups,
            'quote_summary' => $this->getQuoteSummary($worldwideQuote, $outputCurrency),
            'contract_type_name' => $worldwideQuote->contractType->type_short_name,
        ]);
    }

    public function sortGroupsOfPackAssets(WorldwideQuote $quote, bool $forPreview = false): void
    {
        $sortColumn = (string) $quote->activeVersion->sort_assets_groups_column;

        $sortColumn = match ($sortColumn) {
            'assets_sum' => 'assets_sum_price',
            default => $sortColumn,
        };

        $results = $quote->activeVersion->assetsGroups
            ->when(filled($sortColumn),
                static function (Collection $groupsOfRows) use ($quote, $sortColumn): Collection {
                    return $groupsOfRows->sortBy($sortColumn, SORT_NATURAL,
                        $quote->activeVersion->sort_assets_groups_direction === 'desc');
                })
            ->when($forPreview, static function (Collection $groupsOfRows): Collection {
                foreach ($groupsOfRows as $group) {
                    /* @var WorldwideQuoteAssetsGroup $group */
                    $group->setRelation('assets',
                        Collection::make($group->assets->groupBy('serial_no')->collapse()->values()->all()));
                }

                return $groupsOfRows;
            })
            ->values();

        $quote->activeVersion->setRelation('assetsGroups', $results);
    }

    public function collectMappingColumnsOfDistributorQuote(WorldwideDistribution $distributorQuote): array
    {
        /** @var \App\Domain\QuoteFile\Models\ImportedRow[] $mappingRows */
        $importableColumns = $distributorQuote->mappingRows()
            ->toBase()
            ->selectRaw("distinct(json_unquote(json_extract(columns_data, '$.*.importable_column_id'))) as importable_columns")
            ->pluck('importable_columns');

        $importableColumns = $importableColumns->reduce(static function (array $carry, string $columns) {
            return array_merge($carry, json_decode($columns, true));
        }, []);

        /** @var array $importableColumns */
        if (empty($importableColumns)) {
            return [];
        }

        $importableColumns = array_values(array_unique($importableColumns));

        $columnValueQueryBuilder = static function (string $columnKey) use ($distributorQuote): BaseBuilder {
            return $distributorQuote->mappingRows()->getQuery()->toBase()
                ->selectRaw(sprintf("json_unquote(json_extract(columns_data, '$.\"%s\".value')) as value", $columnKey))
                ->selectRaw(sprintf("'%s' as importable_column_id", $columnKey))
                ->selectRaw(sprintf("json_unquote(json_extract(columns_data, '$.\"%s\".header')) as header",
                    $columnKey))
                ->whereRaw(sprintf("json_unquote(json_extract(columns_data, '$.\"%s\".value')) is not null",
                    $columnKey))
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

    public function cloneQuote(WorldwideQuote $quote): WorldwideQuote
    {
        return (new WorldwideQuote())->setRawAttributes($quote->getRawOriginal());
    }
}
