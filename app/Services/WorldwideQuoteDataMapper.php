<?php

namespace App\Services;

use App\Contracts\Services\ManagesExchangeRates;
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
use App\Enum\VAT;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Image;
use App\Models\Quote\DistributionFieldColumn;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Template\ContractTemplate;
use App\Models\Template\QuoteTemplate;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Support\PriceParser;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;

class WorldwideQuoteDataMapper
{
    protected WorldwideQuoteCalc $worldwideQuoteCalc;

    protected WorldwideDistributionCalc $worldwideDistributionCalc;

    protected ManagesExchangeRates $exchangeRateService;

    protected Config $config;

    /** @var bool[]|null */
    protected ?array $requiredFieldsDictionary = null;

    public function __construct(WorldwideQuoteCalc $worldwideQuoteCalc,
                                WorldwideDistributionCalc $worldwideDistributionCalc,
                                ManagesExchangeRates $exchangeRateService,
                                Config $config)
    {
        $this->worldwideQuoteCalc = $worldwideQuoteCalc;
        $this->worldwideDistributionCalc = $worldwideDistributionCalc;
        $this->exchangeRateService = $exchangeRateService;
        $this->config = $config;
    }

    public function isMappingFieldRequired(string $fieldName): bool
    {
        $this->requiredFieldsDictionary ??= array_map(fn(string $name) => true, array_flip($this->config['quote-mapping.worldwide_quote.required_fields'] ?? []));

        return $this->requiredFieldsDictionary[$fieldName] ?? false;
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

    private function mapPackWorldwideQuoteSalesOrderData(WorldwideQuote $worldwideQuote): WorldwideQuoteToSalesOrderData
    {
        $companyData = new SalesOrderCompanyData([
            'id' => $worldwideQuote->company->getKey(),
            'name' => $worldwideQuote->company->name,
            'logo_url' => transform($worldwideQuote->company->logo, function (array $logo) {
                return $logo['x1'] ?? '';
            })
        ]);

        $vendorModelKeys = $worldwideQuote->assets()->distinct('vendor_id')->pluck('vendor_id')->all();

        $vendors = Vendor::query()->whereKey($vendorModelKeys)->with('image')->get(['id', 'name']);

        $vendorsData = $vendors->map(fn(Vendor $vendor) => [
            'id' => $vendor->getKey(),
            'name' => $vendor->name,
            'logo_url' => transform($vendor->logo, function (array $logo) {
                return $logo['x1'] ?? '';
            })
        ])->all();

        $addressModelKeys = $worldwideQuote->assets()->distinct('machine_address_id')->pluck('machine_address_id')->all();

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
            'flag_url' => $country->flag
        ])->all();

        $templates = ContractTemplate::query()
            ->where('business_division_id', BD_WORLDWIDE)
            ->where('contract_type_id', CT_PACK)
            ->where('company_id', $worldwideQuote->company_id)
            ->get(['id', 'name']);

        $templatesData = $templates->map(fn(ContractTemplate $template) => [
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
            'contract_templates' => $templatesData
        ]);
    }

    private function mapContractWorldwideQuoteSalesOrderData(WorldwideQuote $worldwideQuote): WorldwideQuoteToSalesOrderData
    {
        $companyData = new SalesOrderCompanyData([
            'id' => $worldwideQuote->company->getKey(),
            'name' => $worldwideQuote->company->name,
            'logo_url' => transform($worldwideQuote->company->logo, function (array $logo) {
                return $logo['x1'] ?? '';
            })
        ]);

        $distributions = $worldwideQuote->worldwideDistributions()->with('country:id,name,iso_3166_2,flag', 'vendors:id,name,short_code', 'vendors.image')->get(['id', 'country_id']);

        $vendors = $distributions->pluck('vendors')->collapse()->unique('id')->values();

        $vendorsData = $vendors->map(fn(Vendor $vendor) => [
            'id' => $vendor->getKey(),
            'name' => $vendor->name,
            'logo_url' => transform($vendor->logo, function (array $logo) {
                return $logo['x1'] ?? '';
            })
        ])->all();

        $countries = $distributions->pluck('country')->unique('id')->values();

        $countriesData = $countries->map(fn(Country $country) => [
            'id' => $country->getKey(),
            'name' => $country->name,
            'flag_url' => $country->flag
        ])->all();

        $templates = ContractTemplate::query()
            ->where('business_division_id', BD_WORLDWIDE)
            ->where('contract_type_id', CT_CONTRACT)
            ->where('company_id', $worldwideQuote->company_id)
            ->get(['id', 'name']);

        $templatesData = $templates->map(fn(ContractTemplate $template) => [
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
            'contract_templates' => $templatesData
        ]);
    }

    public function mapWorldwideQuotePreviewData(WorldwideQuote $worldwideQuote): WorldwideQuotePreviewData
    {
        if (is_null($worldwideQuote->quoteTemplate)) {
            throw new \RuntimeException("Quote must have a Template to create an export data.");
        }

        $outputCurrency = $this->getQuoteOutputCurrency($worldwideQuote);

        return new WorldwideQuotePreviewData([
            'template_data' => $this->getTemplateData($worldwideQuote),
            'distributions' => $this->getContractQuoteDistributionsData($worldwideQuote, $outputCurrency),
            'pack_assets' => $this->getPackQuoteAssetsData($worldwideQuote, $outputCurrency),
            'pack_asset_fields' => $this->getPackAssetFields($worldwideQuote, $worldwideQuote->quoteTemplate),
            'quote_summary' => $this->getQuoteSummary($worldwideQuote, $outputCurrency),
            'contract_type_name' => $worldwideQuote->contractType->type_short_name
        ]);
    }

    public function mapWorldwideQuotePreviewDataForExport(WorldwideQuote $worldwideQuote): WorldwideQuotePreviewData
    {
        if (is_null($worldwideQuote->quoteTemplate)) {
            throw new \RuntimeException("Quote must have a Template to create an export data.");
        }

        $outputCurrency = $this->getQuoteOutputCurrency($worldwideQuote);

        return new WorldwideQuotePreviewData([
            'template_data' => $this->getTemplateData($worldwideQuote, true),
            'distributions' => $this->getContractQuoteDistributionsData($worldwideQuote, $outputCurrency),
            'pack_assets' => $this->getPackQuoteAssetsData($worldwideQuote, $outputCurrency),
            'pack_asset_fields' => $this->getPackAssetFields($worldwideQuote, $worldwideQuote->quoteTemplate),
            'quote_summary' => $this->getQuoteSummary($worldwideQuote, $outputCurrency),
            'contract_type_name' => $worldwideQuote->contractType->type_short_name
        ]);
    }

    public function getTemplateData(WorldwideQuote $quote, bool $useLocalAssets = false): TemplateData
    {
        $templateSchema = $quote->quoteTemplate->form_data;

        return new TemplateData([
            'first_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['first_page'] ?? []),
            'assets_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['data_pages'] ?? []),
            'payment_schedule_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['payment_schedule'] ?? []),
            'last_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['last_page'] ?? []),
            'template_assets' => $this->getTemplateAssets($quote, $useLocalAssets),
        ]);
    }

    public function getPackQuoteAssetsData(WorldwideQuote $quote, Currency $outputCurrency): array
    {
        if ($quote->contract_type_id !== CT_PACK) {
            return [];
        }

        $quotePriceData = $this->getQuotePriceData($quote);

        $quote->load(['assets' => function (Relation $builder) {
            $builder->where('is_selected', true);
        }]);

        $this->sortWorldwidePackQuoteAssets($quote);

        return $this->worldwideQuoteAssetsToArrayOfAssetData($quote->assets, $quotePriceData->price_value_coefficient, $outputCurrency);
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

        $paymentScheduleFields = $this->getPaymentScheduleFields($worldwideQuote->quoteTemplate);

        $opportunity = $worldwideQuote->opportunity;

        /** @var Contact|null $quoteHardwareContact */
        $quoteHardwareContact = $opportunity->contacts
            ->sortByDesc('pivot.is_default')
            ->first(fn(Contact $contact) => $contact->contact_type === 'Hardware');

        /** @var Contact|null $quoteSoftwareContact */
        $quoteSoftwareContact = $opportunity->contacts
            ->sortByDesc('pivot.is_default')
            ->first(fn(Contact $contact) => $contact->contact_type === 'Software');

        $addressStringFormatter = function (?Address $address): string {
            if (is_null($address)) {
                return '';
            }

            return implode(', ', array_filter([$address->address_1, $address->city, optional($address->country)->iso_3166_2]));
        };

        return $worldwideQuote->worldwideDistributions->map(function (WorldwideDistribution $distribution) use (
            $paymentScheduleFields,
            $worldwideQuote,
            $outputCurrency,
            $quoteHardwareContact,
            $quoteSoftwareContact,
            $addressStringFormatter
        ) {
            if (is_null($distribution->total_price)) {
                $distribution->total_price = $this->worldwideDistributionCalc->calculateDistributionTotalPrice($distribution);
            }

            if (is_null($distribution->final_total_price)) {
                $distributionFinalTotalPrice = $this->worldwideDistributionCalc->calculateDistributionFinalTotalPrice($distribution, (float)$distribution->total_price);

                $distribution->final_total_price = $distributionFinalTotalPrice->final_total_price_value;
            }

            $priceValueCoeff = with($distribution, function (WorldwideDistribution $distribution) {
                if (((float)$distribution->total_price) === 0.0) {
                    return 0.0;
                }

                return $distribution->final_total_price / $distribution->total_price;
            });

            $assetsData = $this->getDistributionAssetsData($distribution, $priceValueCoeff, $outputCurrency);

            $assetFields = $this->getDistributionAssetFields($distribution, $worldwideQuote->quoteTemplate);

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

            $coveragePeriod = '';

            $opportunityStartDate = transform($opportunity->opportunity_start_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));
            $opportunityEndDate = transform($opportunity->opportunity_end_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date));

            if (!is_null($opportunityStartDate) && !is_null($opportunityEndDate)) {
                $coveragePeriod = implode(' ', [
                    static::formatDate($opportunityStartDate),
                    'to',
                    static::formatDate($opportunityEndDate),
                ]);
            }

            /** @var Address|null $quoteHardwareAddress */
            $quoteHardwareAddress = $distribution->addresses
                ->sortByDesc('pivot.is_default')
                ->first(fn(Address $address) => $address->address_type === 'Machine');

            /** @var Address|null $quoteSoftwareAddress */
            $quoteSoftwareAddress = $distribution->addresses
                ->sortByDesc('pivot.is_default')
                ->first(fn(Address $address) => $address->address_type === 'Software');

            return new WorldwideDistributionData([
                'vendors' => $distribution->vendors->pluck('name')->join(', '),
                'country' => $distribution->country->name,
                'assets_data' => $assetsData,
                'mapped_fields_count' => (int)$mappedFieldsCount,
                'assets_are_grouped' => (bool)$distribution->use_groups,
                'asset_fields' => $assetFields,

                'payment_schedule_fields' => $paymentScheduleFields,
                'payment_schedule_data' => $paymentScheduleData,
                'has_payment_schedule_data' => !empty($paymentScheduleData),

                'equipment_address' => $addressStringFormatter($quoteHardwareAddress),
                'hardware_phone' => $quoteHardwareContact->phone ?? '',
                'hardware_contact' => $distribution->opportunitySupplier->contact_name,

                'software_address' => $addressStringFormatter($quoteSoftwareAddress),
                'software_phone' => $quoteSoftwareContact->phone ?? '',
                'software_contact' => $distribution->opportunitySupplier->contact_name,

                'service_levels' => $serviceLevels,
                'coverage_period' => $coveragePeriod,
                'coverage_period_from' => static::formatDate(transform($opportunity->opportunity_start_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date))),
                'coverage_period_to' => static::formatDate(transform($opportunity->opportunity_end_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date))),

                'additional_details' => $distribution->additional_details ?? '',
                'pricing_document' => $distribution->pricing_document ?? '',
                'service_agreement_id' => $distribution->service_agreement_id ?? '',
                'system_handle' => $distribution->system_handle ?? '',
                'purchase_order_number' => $distribution->purchase_order_number ?? '',
                'vat_number' => $distribution->vat_number ?? '',
            ]);
        })->all();
    }

    private function getContractQuoteDataAggregation(WorldwideQuote $quote, Currency $outputCurrency): array
    {
        $quoteDataAggregation = [];

        $opportunity = $quote->opportunity;

        $duration = 'Unknown';

        if (!is_null($opportunity->opportunity_start_date) && !is_null($opportunity->opportunity_end_date)) {
            $opportunityStartDate = Carbon::createFromFormat('Y-m-d', $opportunity->opportunity_start_date);
            $opportunityEndDate = Carbon::createFromFormat('Y-m-d', $opportunity->opportunity_end_date);

            $duration = $opportunityStartDate->longAbsoluteDiffForHumans($opportunityEndDate);
        }

        foreach ($quote->worldwideDistributions as $worldwideDistribution) {
            /** @var WorldwideDistribution $worldwideDistribution */

            $distributionPrice = $this->worldwideDistributionCalc->calculateDistributionTotalPrice($worldwideDistribution);

            $quoteDataAggregation[] = new DistributionSummary([
                'vendor_name' => $worldwideDistribution->vendors->pluck('name')->join(' & '),
                'country_name' => $worldwideDistribution->country->name,
                'duration' => $duration,
                'qty' => 1,
                'total_price' => static::formatPriceValue($distributionPrice, $outputCurrency->symbol),
            ]);
        }

        return $quoteDataAggregation;
    }

    private function getQuotePriceData(WorldwideQuote $worldwideQuote): QuotePriceData
    {
        $quotePriceData = new QuotePriceData();

        $quotePriceData->total_price_value = $this->worldwideQuoteCalc->calculateQuoteTotalPrice($worldwideQuote);
        $quoteFinalPrice = $this->worldwideQuoteCalc->calculateQuoteFinalTotalPrice($worldwideQuote);

        $quotePriceData->final_total_price_value = $quoteFinalPrice->final_total_price_value;
        $quotePriceData->applicable_discounts_value = $quoteFinalPrice->applicable_discounts_value;

        if ($quotePriceData->final_total_price_value !== 0.0) {
            $quotePriceData->price_value_coefficient = $quotePriceData->total_price_value / $quotePriceData->final_total_price_value;
        }

        return $quotePriceData;
    }

    public function getQuoteSummary(WorldwideQuote $worldwideQuote, Currency $outputCurrency): QuoteSummary
    {
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

        $quotePriceData = $this->getQuotePriceData($worldwideQuote);

        /** @var Address|null $quoteHardwareAddress */
        $quoteHardwareAddress = $opportunity->addresses
            ->sortByDesc('pivot.is_default')
            ->first(fn(Address $address) => $address->address_type === 'Machine');

        /** @var Address|null $quoteSoftwareAddress */
        $quoteSoftwareAddress = $opportunity->addresses
            ->sortByDesc('pivot.is_default')
            ->first(fn(Address $address) => $address->address_type === 'Software');

        /** @var Contact|null $quoteHardwareContact */
        $quoteHardwareContact = $opportunity->contacts
            ->sortByDesc('pivot.is_default')
            ->first(fn(Contact $contact) => $contact->contact_type === 'Hardware');

        /** @var Contact|null $quoteSoftwareContact */
        $quoteSoftwareContact = $opportunity->contacts
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

        return new QuoteSummary([
            'company_name' => $worldwideQuote->company->name,
            'customer_name' => $opportunity->primaryAccount->name,
            'quotation_number' => $worldwideQuote->quote_number,
            'export_file_name' => $worldwideQuote->quote_number,

            'service_levels' => '',
            'invoicing_terms' => '',
            'payment_terms' => $worldwideQuote->payment_terms ?? '',

            'support_start' => static::formatDate($opportunityStartDate),
            'support_end' => static::formatDate($opportunityEndDate),
            'valid_until' => static::formatDate($opportunityClosingDate),

            'list_price' => static::formatPriceValue($quotePriceData->total_price_value, $outputCurrency->symbol),
            'applicable_discounts' => static::formatPriceValue($quotePriceData->applicable_discounts_value, $outputCurrency->symbol),
            'final_price' => static::formatPriceValue($quotePriceData->final_total_price_value, $outputCurrency->symbol),

            'quote_price_value_coefficient' => $quotePriceData->price_value_coefficient,

            'contact_name' => optional($opportunity->primaryAccountContact)->contact_name ?? '',
            'contact_email' => optional($opportunity->primaryAccountContact)->email ?? '',
            'contact_phone' => optional($opportunity->primaryAccountContact)->phone ?? '',

            'quote_data_aggregation_fields' => $this->getQuoteDataAggregationFields($worldwideQuote->quoteTemplate),
            'quote_data_aggregation' => $quoteDataAggregation,

            'sub_total_value' => static::formatPriceValue($quotePriceData->final_total_price_value, $outputCurrency->symbol),
            'total_value_including_tax' => '',
            'grand_total_value' => '',

            'equipment_address' => $addressStringFormatter($quoteHardwareAddress),
            'hardware_contact' => $contactStringFormatter($quoteHardwareContact),
            'hardware_phone' => optional($quoteHardwareContact)->phone ?? '',
            'software_address' => $addressStringFormatter($quoteSoftwareAddress),
            'software_contact' => $contactStringFormatter($quoteSoftwareContact),
            'software_phone' => optional($quoteSoftwareContact)->phone ?? '',
            'coverage_period' => '',
            'coverage_period_from' => static::formatDate($opportunityStartDate),
            'coverage_period_to' => static::formatDate($opportunityEndDate),
            'additional_details' => $worldwideQuote->additional_details ?? '',
            'pricing_document' => $worldwideQuote->pricing_document ?? '',
            'service_agreement_id' => $worldwideQuote->service_agreement_id ?? '',
            'system_handle' => $worldwideQuote->system_handle ?? '',
        ]);
    }

    public function sortWorldwidePackQuoteAssets(WorldwideQuote $quote): void
    {
        if (is_null($quote->sort_rows_column) || $quote->sort_rows_column === '') {
            return;
        }

        $results = $quote->assets->sortBy(
            $quote->sort_rows_column,
            SORT_NATURAL,
            $quote->sort_rows_direction === 'desc'
        )
            ->values();

        $quote->setRelation('assets', $results);
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

    private function getPackAssetFields(WorldwideQuote $quote, QuoteTemplate $quoteTemplate): array
    {
        $assetFields = [];

        $templateDataHeaders = $quoteTemplate->data_headers;
        $templateHeadersDictionary = QuoteTemplate::dataHeadersDictionary();

        $assetFieldNames = [
            'vendor_short_code',
            'product_no',
            'description',
            'serial_no',
            'service_level_description',
            'service_sku',
            'date_to',
            'price',
            'machine_address_string'
        ];

        foreach ($assetFieldNames as $fieldName) {
            $fieldHeader = with($fieldName, function (string $fieldName) use ($templateDataHeaders, $templateHeadersDictionary) {
                if (isset($templateDataHeaders[$fieldName])) {
                    return $templateDataHeaders[$fieldName]['value'];
                }

                if (isset($templateHeadersDictionary[$fieldName])) {
                    return $templateHeadersDictionary[$fieldName]['value'];
                }

                return $fieldName;
            });

            $assetFields[] = new AssetField([
                'field_name' => $fieldName,
                'field_header' => $fieldHeader,
            ]);
        }

        return $assetFields;
    }

    private function getDistributionAssetFields(WorldwideDistribution $distribution, QuoteTemplate $quoteTemplate): array
    {
        $assetFields = [];

        $templateDataHeaders = $quoteTemplate->data_headers;
        $templateHeadersDictionary = QuoteTemplate::dataHeadersDictionary();
        $mapping = $distribution->mapping->keyBy('template_field_name');

        $templateFieldNames = [
            'product_no',
            'description',
            'serial_no',
            'service_level_description',
            'service_sku',
            'date_from',
            'date_to',
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

            $fieldHeader = with($column->template_field_name, function (string $fieldName) use ($templateDataHeaders, $templateHeadersDictionary) {

                if (isset($templateDataHeaders[$fieldName])) {
                    return $templateDataHeaders[$fieldName]['value'];
                }

                if (isset($templateHeadersDictionary[$fieldName])) {
                    return $templateHeadersDictionary[$fieldName]['value'];
                }

                return $fieldName;

            });

            $assetFields[] = new AssetField([
                'field_name' => $column->template_field_name,
                'field_header' => $fieldHeader,
            ]);

        }

        return $assetFields;
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

    /**
     * @param QuoteTemplate $quoteTemplate
     * @return AggregationField[]
     */
    private function getQuoteDataAggregationFields(QuoteTemplate $quoteTemplate): array
    {
        $fields = static::getClassPublicProperties(DistributionSummary::class);

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

    private static function formatPriceValue(float $value, string $currencySymbol = ''): string
    {
        $priceValue = number_format($value, 2, '.', '');

        if ($currencySymbol !== '') {
            $priceValue = $currencySymbol.' '.$priceValue;
        }

        return $priceValue;
    }

    private static function formatDate(Carbon $date): string
    {
        return $date->format('d/m/Y');
    }

    private function getQuoteOutputCurrency(WorldwideQuote $worldwideQuote): Currency
    {
        $currency = with($worldwideQuote, function (WorldwideQuote $worldwideQuote) {

            if (is_null($worldwideQuote->output_currency_id)) {
                return $worldwideQuote->quoteCurrency;
            }

            return $worldwideQuote->outputCurrency;

        });

        return tap($currency, function (Currency $currency) use ($worldwideQuote) {

            $currency->exchange_rate_value = $this->exchangeRateService->getTargetRate(
                $worldwideQuote->quoteCurrency,
                $worldwideQuote->outputCurrency
            );

        });
    }

    /**
     * @param WorldwideQuote $quote
     * @param bool $useLocalAssets
     * @return TemplateAssets
     */
    private function getTemplateAssets(WorldwideQuote $quote, bool $useLocalAssets = false): TemplateAssets
    {
        $company = $quote->company;

        $flags = ThumbHelper::WITH_KEYS;

        if ($useLocalAssets) {
            $flags |= ThumbHelper::ABS_PATH;
        }

        $templateAssets = [
            'logo_set_x1' => [],
            'logo_set_x2' => [],
            'logo_set_x3' => []
        ];

        $companyImages = transform($company->image, function (Image $image) use ($flags, $company) {

            return ThumbHelper::getLogoDimensionsFromImage(
                $image,
                $company->thumbnailProperties(),
                '',
                $flags
            );

        }, []);

        $templateAssets['logo_set_x1'] = array_merge($templateAssets['logo_set_x1'], Arr::wrap($companyImages['x1'] ?? []));
        $templateAssets['logo_set_x2'] = array_merge($templateAssets['logo_set_x2'], Arr::wrap($companyImages['x2'] ?? []));
        $templateAssets['logo_set_x3'] = array_merge($templateAssets['logo_set_x3'], Arr::wrap($companyImages['x3'] ?? []));

        /** @var Collection<Vendor>|Vendor[] $vendors */

        $vendors = with($quote, function (WorldwideQuote $quote) {

            if ($quote->contract_type_id === CT_PACK) {

                return Vendor::query()
                    ->whereIn((new Vendor())->getQualifiedKeyName(), function (BaseBuilder $builder) use ($quote) {
                        return $builder->selectRaw('distinct(vendor_id)')
                            ->from('worldwide_quote_assets')
                            ->where($quote->assets()->getQualifiedForeignKeyName(), $quote->getKey())
                            ->whereNotNull('vendor_id')
                            ->where('is_selected', true);
                    })
                    ->has('image')
                    ->get();

            }

            if ($quote->contract_type_id === CT_CONTRACT) {

                $vendors = $quote->worldwideDistributions->load(['vendors' => function (BelongsToMany $relationship) {
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

            $templateAssets['logo_set_x1'] = array_merge($templateAssets['logo_set_x1'], Arr::wrap($vendorImages['x1'] ?? []));
            $templateAssets['logo_set_x2'] = array_merge($templateAssets['logo_set_x2'], Arr::wrap($vendorImages['x2'] ?? []));
            $templateAssets['logo_set_x3'] = array_merge($templateAssets['logo_set_x3'], Arr::wrap($vendorImages['x3'] ?? []));

        }

        return new TemplateAssets($templateAssets);
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
            ]);

        }, $pageSchema);
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

            $this->sortWorldwideDistributionRowsGroups($distribution);

            return $this->mappedRowsToArrayOfAssetData($distribution->mappedRows, $priceValueCoeff, $outputCurrency);
        }

        $distribution->load(['rowsGroups' => function (Relation $builder) {
            $builder->where('is_selected', true);
        }, 'rowsGroups.rows']);

        $this->sortWorldwideDistributionRows($distribution);

        return $distribution->rowsGroups->map(function (DistributionRowsGroup $rowsGroup) use ($priceValueCoeff, $outputCurrency, $distribution) {
            $assets = $this->mappedRowsToArrayOfAssetData($rowsGroup->rows, $priceValueCoeff * (float)$outputCurrency->exchange_rate_value, $outputCurrency);

            return new AssetsGroupData([
                'group_name' => $rowsGroup->group_name,
                'assets' => $assets,
                'group_total_price' => static::formatPriceValue((float)$rowsGroup->rows_sum * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value, $outputCurrency->symbol),
                'group_total_price_float' => (float)$rowsGroup->rows_sum * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value,
            ]);

        })->all();

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
            return new AssetData([
                'vendor_short_code' => '',
                'product_no' => $row->product_no ?? '',
                'service_sku' => $row->service_sku ?? '',
                'description' => $row->description ?? '',
                'serial_no' => $row->serial_no ?? '',
                'date_from' => transform($row->date_from, function (string $date) {
                    /** @noinspection PhpParamsInspection */
                    return static::formatDate(Carbon::createFromFormat('Y-m-d', $date));
                }, ''),
                'date_to' => transform($row->date_to, function (string $date) {
                    /** @noinspection PhpParamsInspection */
                    return static::formatDate(Carbon::createFromFormat('Y-m-d', $date));
                }, ''),
                'qty' => $row->qty ?? 1,
                'price' => static::formatPriceValue((float)$row->price * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value, $outputCurrency->symbol),
                'price_float' => (float)$row->price * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value,
                'machine_address_string' => '',
                'pricing_document' => $row->pricing_document ?? '',
                'system_handle' => $row->system_handle ?? '',
                'searchable' => $row->searchable ?? '',
                'service_level_description' => $row->service_level_description ?? '',
            ]);
        })->all();
    }

    private function worldwideQuoteAssetsToArrayOfAssetData(Collection $assets, float $priceValueCoeff, Currency $outputCurrency): array
    {
        return $assets
            ->load('machineAddress.country')
            ->map(function (WorldwideQuoteAsset $asset) use ($outputCurrency, $priceValueCoeff) {
                $assetFloatPrice = (float)$asset->price * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value;

                return new AssetData([
                    'vendor_short_code' => $asset->vendor_short_code ?? '',
                    'product_no' => $asset->sku ?? '',
                    'service_sku' => $asset->service_sku ?? '',
                    'description' => $asset->product_name ?? '',
                    'serial_no' => $asset->serial_no ?? '',
                    'date_from' => '',
                    'date_to' => transform($asset->expiry_date, function (string $date) {
                        /** @noinspection PhpParamsInspection */
                        return static::formatDate(Carbon::createFromFormat('Y-m-d', $date));
                    }, ''),
                    'qty' => 1,
                    'price' => static::formatPriceValue($assetFloatPrice, $outputCurrency->symbol),
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

    /**
     * @param array $value
     * @param float $priceValueCoeff
     * @param Currency $outputCurrency
     * @return PaymentData[]
     */
    private function paymentScheduleToArrayOfPaymentData(array $value, float $priceValueCoeff, Currency $outputCurrency): array
    {
        return array_map(function (array $payment) use ($priceValueCoeff, $outputCurrency) {

            return new PaymentData([
                'from' => $payment['from'],
                'to' => $payment['to'],
                'price' => static::formatPriceValue((float)PriceParser::parseAmount($payment['price'] ?? '') * $priceValueCoeff * (float)$outputCurrency->exchange_rate_value, $outputCurrency->symbol),
            ]);

        }, $value);
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
}
