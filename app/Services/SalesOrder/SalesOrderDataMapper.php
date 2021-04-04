<?php

namespace App\Services\SalesOrder;

use App\Contracts\Services\ManagesExchangeRates;
use App\DTO\SalesOrder\Submit\SubmitOrderAddressData;
use App\DTO\SalesOrder\Submit\SubmitOrderCustomerData;
use App\DTO\SalesOrder\Submit\SubmitOrderLineData;
use App\DTO\SalesOrder\Submit\SubmitSalesOrderData;
use App\DTO\Template\TemplateElement;
use App\DTO\WorldwideQuote\AssetServiceLevel;
use App\DTO\WorldwideQuote\AssetServiceLookupData;
use App\DTO\WorldwideQuote\AssetServiceLookupDataCollection;
use App\DTO\WorldwideQuote\Export\AssetField;
use App\DTO\WorldwideQuote\Export\QuotePriceData;
use App\DTO\WorldwideQuote\Export\TemplateAssets;
use App\DTO\WorldwideQuote\Export\TemplateData;
use App\DTO\WorldwideQuote\Export\WorldwideQuotePreviewData;
use App\Enum\VAT;
use App\Models\Address;
use App\Models\Company;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Data\ExchangeRate;
use App\Models\Image;
use App\Models\Opportunity;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Services\ThumbHelper;
use App\Services\WorldwideQuote\AssetServiceLookupService;
use App\Services\WorldwideQuoteCalc;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Arr;

class SalesOrderDataMapper
{
    const EXCLUDED_ASSET_FIELDS = ['price'];

    protected ManagesExchangeRates $exchangeRateService;

    protected AssetServiceLookupService $lookupService;

    protected WorldwideQuoteCalc $quoteCalc;

    public function __construct(ManagesExchangeRates $exchangeRateService, AssetServiceLookupService $lookupService, WorldwideQuoteCalc $quoteCalc)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->lookupService = $lookupService;
        $this->quoteCalc = $quoteCalc;
    }

    public function mapSalesOrderToSubmitSalesOrderData(SalesOrder $salesOrder): SubmitSalesOrderData
    {
        $contractTypeKey = $salesOrder->worldwideQuote->contract_type_id;

        if ($contractTypeKey === CT_CONTRACT) {
            return $this->mapContractSalesOrderToSubmitSalesOrderData($salesOrder);
        }

        if ($contractTypeKey === CT_PACK) {
            return $this->mapPackSalesOrderToSubmitSalesOrderData($salesOrder);
        }

        throw new \RuntimeException('Unsupported Quote Contract Type to map Submit Sales Order Data.');
    }

    private function getSalesOrderPriceData(SalesOrder $salesOrder): QuotePriceData
    {
        $quotePriceData = new QuotePriceData();

        $quotePriceData->total_price_value = $this->quoteCalc->calculateQuoteTotalPrice($salesOrder->worldwideQuote);
        $quoteFinalPrice = $this->quoteCalc->calculateQuoteFinalTotalPrice($salesOrder->worldwideQuote);

        $quotePriceData->final_total_price_value = $quoteFinalPrice->final_total_price_value;
        $quotePriceData->applicable_discounts_value = $quoteFinalPrice->applicable_discounts_value;

        if ($quotePriceData->final_total_price_value !== 0.0) {
            $quotePriceData->price_value_coefficient = $quotePriceData->total_price_value / $quotePriceData->final_total_price_value;
        }

        return $quotePriceData;
    }

    private function mapContractSalesOrderToSubmitSalesOrderData(SalesOrder $salesOrder): SubmitSalesOrderData
    {
        $quote = $salesOrder->worldwideQuote;
        $opportunity = $quote->opportunity;
        $customer = $opportunity->primaryAccount;
        $quoteCurrency = $this->getSalesOrderOutputCurrency($salesOrder);
        $accountManager = $opportunity->accountManager;

        $activeQuoteVersion = $quote->activeVersion;

        $company = $activeQuoteVersion->company;

        if ($activeQuoteVersion->worldwideDistributions->isEmpty()) {
            throw new \InvalidArgumentException("At least one Distributor Quote must exist on the Quote.");
        }

        $quotePriceData = $this->getSalesOrderPriceData($salesOrder);

        $activeQuoteVersion->worldwideDistributions->load(['mappedRows' => function (Relation $relation) {
            $relation->where('is_selected', true);
        }, 'rowsGroups' => function (Relation $relation) {
            $relation->where('is_selected', true);
        }, 'rowsGroups.rows']);

        $activeQuoteVersion->worldwideDistributions->each(function (WorldwideDistribution $distributorQuote) {

            $supplierName = $distributorQuote->opportunitySupplier->supplier_name;

            if ($distributorQuote->vendors->isEmpty()) {
                throw new \InvalidArgumentException("No vendors defined, Supplier: '{$supplierName}'.");
            }

            if (is_null($distributorQuote->country)) {
                throw new \InvalidArgumentException("Country must be defined, Supplier: '{$supplierName}'.");
            }

        });

        $orderLinesData = $activeQuoteVersion->worldwideDistributions->reduce(function (array $orderLines, WorldwideDistribution $distribution) use ($quoteCurrency, $quotePriceData) {
            $distributorQuoteLines = value(function () use ($quotePriceData, $quoteCurrency, $distribution): array {
                /** @var Vendor $quoteVendor */
                $quoteVendor = $distribution->vendors->first();

                $supplier = $distribution->opportunitySupplier;

                if ($distribution->use_groups) {

                    return $distribution->rowsGroups->reduce(function (array $rows, DistributionRowsGroup $rowsGroup) use ($supplier, $quoteVendor, $quotePriceData, $quoteCurrency, $distribution) {
                        $rowsOfGroup = $rowsGroup->rows->map(fn(MappedRow $row) => new SubmitOrderLineData([
                            'line_id' => $row->getKey(),
                            'unit_price' => $row->price,
                            'sku' => $row->product_no ?? '',
                            'buy_price' => $row->price * $quotePriceData->price_value_coefficient * (float)$quoteCurrency->exchange_rate_value,
                            'service_description' => $row->service_level_description ?? '',
                            'product_description' => $row->description ?? '',
                            'serial_number' => $row->serial_no ?? '',
                            'quantity' => $row->qty,
                            'service_sku' => $row->service_sku ?? '',
                            'vendor_short_code' => $quoteVendor->short_code ?? '',
                            'distributor_name' => $supplier->supplier_name ?? '',
                            'discount_applied' => $quotePriceData->applicable_discounts_value > 0,
                            'machine_country_code' => transform($distribution->country, fn(Country $country) => $country->iso_3166_2) ?? '',
                            'currency_code' => $quoteCurrency->code,
                        ]))->all();

                        return array_merge($rows, $rowsOfGroup);
                    }, []);

                }

                return $distribution->mappedRows->map(function (MappedRow $row) use ($distribution, $quoteCurrency, $supplier, $quoteVendor, $quotePriceData) {
                    return new SubmitOrderLineData([
                        'line_id' => $row->getKey(),
                        'unit_price' => $row->price,
                        'sku' => $row->product_no ?? '',
                        'buy_price' => $row->price * $quotePriceData->price_value_coefficient * (float)$quoteCurrency->exchange_rate_value,
                        'service_description' => $row->service_level_description ?? '',
                        'product_description' => $row->description ?? '',
                        'serial_number' => $row->serial_no ?? '',
                        'quantity' => $row->qty,
                        'service_sku' => $row->service_sku ?? '',
                        'vendor_short_code' => $quoteVendor->short_code ?? '',
                        'distributor_name' => $supplier->supplier_name ?? '',
                        'discount_applied' => $quotePriceData->applicable_discounts_value > 0,
                        'machine_country_code' => transform($distribution->country, fn(Country $country) => $country->iso_3166_2) ?? '',
                        'currency_code' => $quoteCurrency->code,
                    ]);
                })->all();
            });

            return array_merge($distributorQuoteLines);
        }, []);

        if (empty($orderLinesData)) {
            throw new \InvalidArgumentException('At least one Order Line must exist on the Quote.');
        }

//        $this->populateServiceDataToSupportLines(...$orderLinesData);

        $addresses = [];

        foreach ($activeQuoteVersion->worldwideDistributions as $distributorQuote) {
            $addresses[] = $distributorQuote->addresses
                ->sortByDesc('pivot.is_default')
                ->first(function (Address $address) {
                    return $address->address_type === 'Machine';
                });

            $addresses[] = $distributorQuote->addresses
                ->sortByDesc('pivot.is_default')
                ->first(function (Address $address) {
                    return $address->address_type === 'Invoice';
                });
        }

        $submitAddressDataMapper = function (Address $address): SubmitOrderAddressData {
            return new SubmitOrderAddressData([
                'address_type' => $address->address_type,
                'address_1' => $address->address_1 ?? '',
                'address_2' => $address->address_2,
                'state' => $address->state,
                'state_code' => $address->state_code,
                'country_code' => transform($address->country, fn(Country $country) => $country->iso_3166_2) ?? '',
                'city' => $address->city,
                'post_code' => $address->post_code
            ]);
        };

        $addressesData = array_map($submitAddressDataMapper, array_values(array_filter($addresses)));

        $customerData = new SubmitOrderCustomerData([
            'customer_name' => $customer->name,
            'email' => $this->resolveEmailOfPrimaryAccount($opportunity),
            'phone_no' => $customer->phone,
            'currency_code' => $quoteCurrency->code,
            'fax_no' => null,
            'company_reg_no' => null,
            'vat_reg_no' => $this->resolveSalesOrderVat($salesOrder)
        ]);

        /** @var WorldwideDistribution|null $firstDistributorQuote */
        $firstDistributorQuote = $activeQuoteVersion->worldwideDistributions->first();

        /** @var Vendor|null $firstVendor */
        $firstVendor = $firstDistributorQuote->vendors->first();

        $exchangeRateValue = $this->getExchangeRateValueOfCurrency($quoteCurrency->code);

        return new SubmitSalesOrderData([
            'addresses_data' => $addressesData,
            'customer_data' => $customerData,
            'order_lines_data' => $orderLinesData,
            'vendor_short_code' => $firstVendor->short_code,
            'contract_type' => $quote->contractType->type_short_name,
            'registration_customer_name' => null,
            'currency_code' => $quoteCurrency->code,
            'from_date' => $opportunity->opportunity_start_date,
            'to_date' => $opportunity->opportunity_end_date,
            'service_agreement_id' => $quote->quote_number,
            'order_no' => $salesOrder->order_number,
            'order_date' => now()->toDateString(),
            'bc_company_name' => transform($company, fn(Company $company) => $company->vs_company_code),
            'exchange_rate' => $exchangeRateValue,
            'post_sales_id' => $salesOrder->getKey(),
            'customer_po' => $salesOrder->customer_po,
            'sales_person_name' => transform($accountManager, function (User $accountManager) {
                return sprintf('%s %s', $accountManager->first_name, $accountManager->last_name);
            }, '')
        ]);
    }

    private function mapPackSalesOrderToSubmitSalesOrderData(SalesOrder $salesOrder): SubmitSalesOrderData
    {
        $quote = $salesOrder->worldwideQuote;
        $quoteActiveVersion = $quote->activeVersion;
        $opportunity = $quote->opportunity;
        $customer = $opportunity->primaryAccount;
        $quoteCurrency = $this->getSalesOrderOutputCurrency($salesOrder);
        $accountManager = $opportunity->accountManager;
        $company = $quoteActiveVersion->company;

        $quoteActiveVersion->load(['assets' => function (MorphMany $relation) {
            $relation->where('is_selected', true);
        }, 'assets.machineAddress.country']);

        if ($quoteActiveVersion->assets->isEmpty()) {
            throw new \InvalidArgumentException("At least one Pack Asset must exist on the Quote.");
        }

        $quotePriceData = $this->getSalesOrderPriceData($salesOrder);

        $orderLinesData = $quoteActiveVersion->assets->map(function (WorldwideQuoteAsset $row) use ($quoteCurrency, $quotePriceData) {
            return new SubmitOrderLineData([
                'line_id' => $row->getKey(),
                'unit_price' => $row->price,
                'sku' => $row->sku ?? '',
                'buy_price' => $row->price * $quotePriceData->price_value_coefficient * (float)$quoteCurrency->exchange_rate_value,
                'service_description' => $row->service_level_description ?? '',
                'product_description' => $row->product_name ?? '',
                'serial_number' => $row->serial_no ?? '',
                'quantity' => 1,
                'service_sku' => $row->service_sku ?? '',
                'vendor_short_code' => $row->vendor_short_code ?? '',
                'distributor_name' => null,
                'discount_applied' => $quotePriceData->applicable_discounts_value > 0,
                'machine_country_code' => transform($row->machineAddress, function (Address $address): string {
                    if (!is_null($address->country)) {
                        return $address->country->iso_3166_2;
                    }

                    return '';
                }),
                'currency_code' => $quoteCurrency->code,
            ]);
        })->all();

//        $this->populateServiceDataToSupportLines(...$orderLinesData);

        $addresses = [];

        $addresses[] = $opportunity->addresses
            ->sortByDesc('pivot.is_default')
            ->first(function (Address $address) {
                return $address->address_type === 'Machine';
            });

        $addresses[] = $opportunity->addresses
            ->sortByDesc('pivot.is_default')
            ->first(function (Address $address) {
                return $address->address_type === 'Invoice';
            });

        $submitAddressDataMapper = function (Address $address): SubmitOrderAddressData {
            return new SubmitOrderAddressData([
                'address_type' => $address->address_type,
                'address_1' => $address->address_1 ?? '',
                'address_2' => $address->address_2,
                'state' => $address->state,
                'state_code' => $address->state_code,
                'country_code' => transform($address->country, fn(Country $country) => $country->iso_3166_2) ?? '',
                'city' => $address->city,
                'post_code' => $address->post_code
            ]);
        };

        $addressesData = array_map($submitAddressDataMapper, array_values(array_filter($addresses)));

        $customerData = new SubmitOrderCustomerData([
            'customer_name' => $customer->name,
            'email' => $this->resolveEmailOfPrimaryAccount($opportunity),
            'phone_no' => $customer->phone,
            'fax_no' => null,
            'company_reg_no' => null,
            'vat_reg_no' => $this->resolveSalesOrderVat($salesOrder),
            'currency_code' => $quoteCurrency->code,
        ]);

        /** @var WorldwideQuoteAsset $firstAsset */
        $firstAsset = $quote->assets->first();

        $exchangeRateValue = $this->getExchangeRateValueOfCurrency($quoteCurrency->code);

        return new SubmitSalesOrderData([
            'addresses_data' => $addressesData,
            'customer_data' => $customerData,
            'order_lines_data' => $orderLinesData,
            'vendor_short_code' => $firstAsset->vendor->short_code,
            'contract_type' => $quote->contractType->type_short_name,
            'registration_customer_name' => null,
            'currency_code' => $quoteCurrency->code,
            'from_date' => $opportunity->opportunity_start_date,
            'to_date' => $opportunity->opportunity_end_date,
            'service_agreement_id' => $quote->quote_number,
            'order_no' => $salesOrder->order_number,
            'order_date' => now()->toDateString(),
            'bc_company_name' => transform($company, fn(Company $company) => $company->vs_company_code),
            'exchange_rate' => $exchangeRateValue,
            'post_sales_id' => $salesOrder->getKey(),
            'customer_po' => $salesOrder->customer_po,
            'sales_person_name' => transform($accountManager, function (User $accountManager) {
                return sprintf('%s %s', $accountManager->first_name, $accountManager->last_name);
            }, '')
        ]);
    }

    private function resolveCustomerVat(Company $company): ?string
    {
        if ($company->vat_type === VAT::VAT_NUMBER) {
            return $company->vat;
        }

        return $company->vat_type;
    }

    private function resolveSalesOrderVat(SalesOrder $salesOrder): ?string
    {
        if ($salesOrder->vat_type === VAT::VAT_NUMBER) {
            return $salesOrder->vat_number;
        }

        return $salesOrder->vat_type;
    }

    private function populateServiceDataToSupportLines(SubmitOrderLineData ...$orderLines): void
    {
        $lineDictionary = [];
        $lookupDataDictionary = [];

        foreach ($orderLines as $lineData) {

            if (
                in_array($lineData->vendor_short_code, AssetServiceLookupService::SUPPORTED_VENDORS) &&
                trim($lineData->serial_number) !== '' &&
                trim($lineData->sku) !== '' &&
                trim($lineData->machine_country_code) !== ''
            ) {
                $lookupDataDictionary[$lineData->line_id] = new AssetServiceLookupData([
                    'asset_id' => $lineData->line_id,
                    'vendor_short_code' => $lineData->vendor_short_code,
                    'serial_no' => $lineData->serial_number,
                    'sku' => $lineData->sku,
                    'country_code' => $lineData->machine_country_code,
                    'currency_code' => $lineData->currency_code,
                ]);

                $lineDictionary[$lineData->line_id] = $lineData;
            }

        }

        if (empty($lookupDataDictionary)) {
            return;
        }

        $lookupCollection = new AssetServiceLookupDataCollection($lookupDataDictionary);

        $lookupResult = $this->lookupService->performBatchWarrantyLookup($lookupCollection);

        $serviceLevelCodeFinder = function (string $serviceLevelDescription, array $serviceLevels): string {
            $serviceLevelDescription = trim($serviceLevelDescription);

            foreach ($serviceLevels as $serviceLevel) {
                /** @var AssetServiceLevel $serviceLevel */

                if (trim($serviceLevel->description) === $serviceLevelDescription) {
                    return $serviceLevel->code ?? '';
                }
            }

            return '';
        };

        foreach ($lookupResult as $key => $result) {
            $lineData = $lineDictionary[$key];

            if (!is_null($lineData->service_description)) {
                $lineData->service_sku = $serviceLevelCodeFinder(
                    $lineData->service_description,
                    $result->service_levels
                );
            }

        }
    }

    private function resolveEmailOfPrimaryAccount(Opportunity $opportunity): string
    {
        if (!is_null($opportunity->primaryAccount->email) && trim($opportunity->primaryAccount->email) !== '') {
            return $opportunity->primaryAccount->email;
        }

        $defaultContact = $opportunity->contacts
            ->sortByDesc('pivot.is_default')
            ->whereNotNull('email')
            ->first();

        if (!is_null($defaultContact)) {
            return $defaultContact->email ?? '';
        }

        return '';
    }

    private function getExchangeRateValueOfCurrency(string $currencyCode): ?float
    {
        if ($currencyCode === $this->exchangeRateService->baseCurrency()) {
            return 1.0;
        }

        $exchangeRateValue = ExchangeRate::query()
            ->where('currency_code', $currencyCode)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->value('exchange_rate');

        if (!is_null($exchangeRateValue)) {
            return (float)$exchangeRateValue;
        }

        $rateData = $this->exchangeRateService->getRateDataOfCurrency($currencyCode, now());

        if (!is_null($rateData)) {
            return $rateData->exchange_rate;
        }

        return null;
    }

    private function getSalesOrderOutputCurrency(SalesOrder $salesOrder): Currency
    {
        $quoteActiveVersion = $salesOrder->worldwideQuote->activeVersion;

        $currency = with($quoteActiveVersion, function (WorldwideQuoteVersion $worldwideQuote) {

            if (is_null($worldwideQuote->output_currency_id)) {
                return $worldwideQuote->quoteCurrency;
            }

            return $worldwideQuote->outputCurrency;

        });

        return tap($currency, function (Currency $currency) use ($quoteActiveVersion, $salesOrder) {

            $currency->exchange_rate_value = $this->exchangeRateService->getTargetRate(
                $quoteActiveVersion->quoteCurrency,
                $quoteActiveVersion->outputCurrency
            );

        });
    }

    public function mapWorldwideQuotePreviewDataAsSalesOrderPreviewData(SalesOrder $salesOrder, WorldwideQuotePreviewData $quotePreviewData): WorldwideQuotePreviewData
    {
        return tap($quotePreviewData, function (WorldwideQuotePreviewData $quotePreviewData) use ($salesOrder) {
            $quote = $salesOrder->worldwideQuote;

            $quotePreviewData->pack_asset_fields = static::excludeAssetFields($quotePreviewData->pack_asset_fields, self::EXCLUDED_ASSET_FIELDS);

            foreach ($quotePreviewData->distributions as $distribution) {
                $distribution->asset_fields = static::excludeAssetFields($distribution->asset_fields, self::EXCLUDED_ASSET_FIELDS);
            }

            $quotePreviewData->template_data = $this->getTemplateData($salesOrder);

            $quotePreviewData->quote_summary->vat_number = (string)$salesOrder->vat_number;
            $quotePreviewData->quote_summary->purchase_order_number = (string)$salesOrder->customer_po;
            $quotePreviewData->quote_summary->export_file_name = SalesOrderNumberHelper::makeSalesOrderNumber(
                $quotePreviewData->contract_type_name,
                $quote->sequence_number
            );
        });
    }

    public function getTemplateData(SalesOrder $salesOrder, bool $useLocalAssets = false): TemplateData
    {
        $templateSchema = $salesOrder->contractTemplate->form_data;

        return new TemplateData([
            'first_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['first_page'] ?? []),
            'assets_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['data_pages'] ?? []),
            'payment_schedule_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['payment_schedule'] ?? []),
            'last_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['last_page'] ?? []),
            'template_assets' => $this->getTemplateAssets($salesOrder, $useLocalAssets),
        ]);
    }

    /**
     * @param SalesOrder $salesOrder
     * @param bool $useLocalAssets
     * @return TemplateAssets
     */
    private function getTemplateAssets(SalesOrder $salesOrder, bool $useLocalAssets = false): TemplateAssets
    {
        $quote = $salesOrder->worldwideQuote;

        $company = $quote->activeVersion->company;

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
     * @param array $assetFields
     * @param array $fieldNames
     * @return array
     */
    private static function excludeAssetFields(array $assetFields, array $fieldNames): array
    {
        return array_values(array_filter($assetFields, function (AssetField $assetField) use ($fieldNames) {
            return !in_array($assetField->field_name, $fieldNames, true);
        }));
    }
}
