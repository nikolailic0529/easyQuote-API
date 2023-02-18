<?php

namespace App\Domain\Worldwide\Services\SalesOrder;

use App\Domain\Address\Enum\AddressType;
use App\Domain\Address\Models\Address;
use App\Domain\Company\Enum\VAT;
use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates;
use App\Domain\Image\Models\Image;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Template\DataTransferObjects\TemplateElement;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\DataTransferObjects\Quote\AssetServiceLevel;
use App\Domain\Worldwide\DataTransferObjects\Quote\AssetServiceLookupData;
use App\Domain\Worldwide\DataTransferObjects\Quote\AssetServiceLookupDataCollection;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetField;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\QuotePriceData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\TemplateAssets;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\TemplateData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\WorldwideQuotePreviewData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderAddressData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderCustomerData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderLineData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitSalesOrderData;
use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Domain\Worldwide\Services\WorldwideQuote\AssetServiceLookupService;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
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
    protected WorldwideDistributorQuoteCalc $distributorQuoteCalc;

    public function __construct(ManagesExchangeRates $exchangeRateService,
                                AssetServiceLookupService $lookupService,
                                WorldwideQuoteCalc $quoteCalc,
                                WorldwideDistributorQuoteCalc $distributorQuoteCalc)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->lookupService = $lookupService;
        $this->quoteCalc = $quoteCalc;
        $this->distributorQuoteCalc = $distributorQuoteCalc;
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
            throw new \InvalidArgumentException('At least one Distributor Quote must exist on the Quote.');
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

        $orderLinesData = $activeQuoteVersion->worldwideDistributions->reduce(function (array $orderLines, WorldwideDistribution $distribution) use ($quoteCurrency) {
            $priceSummaryOfDistributorQuote = $this->distributorQuoteCalc->calculatePriceSummaryOfDistributorQuote($distribution);

            $priceValueCoeff = with($priceSummaryOfDistributorQuote, function (ImmutablePriceSummaryData $priceSummaryData): float {
                if ($priceSummaryData->total_price !== 0.0) {
                    return $priceSummaryData->final_total_price_excluding_tax / $priceSummaryData->total_price;
                }

                return 0.0;
            });

            $distributorQuoteLines = value(function () use ($priceSummaryOfDistributorQuote, $priceValueCoeff, $quoteCurrency, $distribution): array {
                /** @var \App\Domain\Vendor\Models\Vendor $quoteVendor */
                $quoteVendor = $distribution->vendors->first();

                $supplier = $distribution->opportunitySupplier;

                if ($distribution->use_groups) {
                    return $distribution->rowsGroups->reduce(function (array $rows, DistributionRowsGroup $rowsGroup) use ($supplier, $quoteVendor, $priceValueCoeff, $priceSummaryOfDistributorQuote, $quoteCurrency, $distribution) {
                        $rowsOfGroup = $rowsGroup->rows->map(fn (MappedRow $row) => new SubmitOrderLineData([
                            'line_id' => $row->getKey(),
                            'unit_price' => $row->price,
                            'sku' => $row->product_no ?? '',
                            'buy_price' => $row->price * $priceValueCoeff * (float) $quoteCurrency->exchange_rate_value,
                            'service_description' => $row->service_level_description ?? '',
                            'product_description' => $row->description ?? '',
                            'serial_number' => $row->serial_no ?? '',
                            'quantity' => $row->qty,
                            'service_sku' => $row->service_sku ?? '',
                            'vendor_short_code' => $quoteVendor->short_code ?? '',
                            'distributor_name' => $supplier->supplier_name ?? '',
                            'discount_applied' => $priceSummaryOfDistributorQuote->applicable_discounts_value > 0,
                            'machine_country_code' => transform($distribution->country, fn (Country $country) => $country->iso_3166_2) ?? '',
                            'currency_code' => $quoteCurrency->code,
                        ]))->all();

                        return array_merge($rows, $rowsOfGroup);
                    }, []);
                }

                return $distribution->mappedRows->map(function (MappedRow $row) use ($distribution, $quoteCurrency, $supplier, $quoteVendor, $priceValueCoeff, $priceSummaryOfDistributorQuote) {
                    return new SubmitOrderLineData([
                        'line_id' => $row->getKey(),
                        'unit_price' => $row->price,
                        'sku' => $row->product_no ?? '',
                        'buy_price' => $row->price * $priceValueCoeff * (float) $quoteCurrency->exchange_rate_value,
                        'service_description' => $row->service_level_description ?? '',
                        'product_description' => $row->description ?? '',
                        'serial_number' => $row->serial_no ?? '',
                        'quantity' => $row->qty,
                        'service_sku' => $row->service_sku ?? '',
                        'vendor_short_code' => $quoteVendor->short_code ?? '',
                        'distributor_name' => $supplier->supplier_name ?? '',
                        'discount_applied' => $priceSummaryOfDistributorQuote->applicable_discounts_value > 0,
                        'machine_country_code' => transform($distribution->country, fn (Country $country) => $country->iso_3166_2) ?? '',
                        'currency_code' => $quoteCurrency->code,
                    ]);
                })->all();
            });

            return array_merge($orderLines, $distributorQuoteLines);
        }, []);

        if (empty($orderLinesData)) {
            throw new \InvalidArgumentException('At least one Order Line must exist on the Quote.');
        }

        $addresses = [];

        $addresses[] = $activeQuoteVersion->worldwideQuote->opportunity->primaryAccount->addresses
            ->sortByDesc('pivot.is_default')
            ->first(function (Address $address) {
                return in_array($address->address_type, [AddressType::HARDWARE, AddressType::MACHINE]);
            });

        $addresses[] = $activeQuoteVersion->worldwideQuote->opportunity->primaryAccount->addresses
            ->sortByDesc('pivot.is_default')
            ->first(function (Address $address) {
                return $address->address_type === AddressType::INVOICE;
            });

        $submitAddressDataMapper = function (Address $address): SubmitOrderAddressData {
            return new SubmitOrderAddressData([
                'address_type' => $address->address_type,
                'address_1' => $address->address_1 ?? '',
                'address_2' => $address->address_2,
                'state' => $address->state,
                'state_code' => $address->state_code,
                'country_code' => transform($address->country, fn (Country $country) => $country->iso_3166_2) ?? '',
                'city' => $address->city,
                'post_code' => $address->post_code,
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
            'vat_reg_no' => $this->resolveSalesOrderVat($salesOrder),
        ]);

        /** @var WorldwideDistribution|null $firstDistributorQuote */
        $firstDistributorQuote = $activeQuoteVersion->worldwideDistributions->first();

        /** @var \App\Domain\Vendor\Models\Vendor|null $firstVendor */
        $firstVendor = $firstDistributorQuote->vendors->first();

        $exchangeRateValue = $this->exchangeRateService->getRateByCurrencyCode(
            $quoteCurrency->code, now(), ManagesExchangeRates::FALLBACK_LATEST
        );

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
            'service_agreement_id' => $salesOrder->contract_number,
            'order_no' => $salesOrder->order_number,
            'order_date' => now()->toDateString(),
            'bc_company_name' => transform($company, fn (Company $company) => $company->vs_company_code),
            'company_id' => transform($company, fn (Company $company) => $company->getKey()),
            'exchange_rate' => $exchangeRateValue,
            'post_sales_id' => $salesOrder->getKey(),
            'customer_po' => $salesOrder->customer_po,
            'sales_person_name' => transform($accountManager, function (User $accountManager) {
                return sprintf('%s %s', $accountManager->first_name, $accountManager->last_name);
            }, ''),
        ]);
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

        return tap($currency, function (Currency $currency) use ($quoteActiveVersion) {
            $currency->exchange_rate_value = $this->exchangeRateService->getTargetRate(
                $quoteActiveVersion->quoteCurrency,
                $quoteActiveVersion->outputCurrency
            );
        });
    }

    private function getSalesOrderPriceData(SalesOrder $salesOrder): QuotePriceData
    {
        return tap(new QuotePriceData(), function (QuotePriceData $quotePriceData) use ($salesOrder) {
            $priceSummary = $this->quoteCalc->calculatePriceSummaryOfQuote($salesOrder->worldwideQuote);

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

    private function resolveEmailOfPrimaryAccount(Opportunity $opportunity): string
    {
        if (!is_null($opportunity->primaryAccount->email) && trim($opportunity->primaryAccount->email) !== '') {
            return $opportunity->primaryAccount->email;
        }

        return '';
    }

    private function resolveSalesOrderVat(SalesOrder $salesOrder): ?string
    {
        if ($salesOrder->vat_type === VAT::VAT_NUMBER) {
            return $salesOrder->vat_number;
        }

        return $salesOrder->vat_type;
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

        $quotePriceData = $this->getSalesOrderPriceData($salesOrder);

        $quoteAssetToOrderLineData = function (WorldwideQuoteAsset $row) use ($quotePriceData, $quoteCurrency): SubmitOrderLineData {
            return new SubmitOrderLineData([
                'line_id' => $row->getKey(),
                'unit_price' => $row->price,
                'sku' => $row->sku ?? '',
                'buy_price' => $row->price * $quotePriceData->price_value_coefficient * (float) $quoteCurrency->exchange_rate_value,
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
        };

        $orderLinesData = value(function () use ($quoteAssetToOrderLineData, $quoteActiveVersion) {
            if (!$quoteActiveVersion->use_groups) {
                $quoteActiveVersion->load(['assets' => function (MorphMany $relation) {
                    $relation->where('is_selected', true);
                }, 'assets.machineAddress.country']);

                return $quoteActiveVersion->assets->map($quoteAssetToOrderLineData)->all();
            }

            $quoteActiveVersion->load(['assetsGroups' => function (Relation $relation) {
                $relation->where('is_selected', true);
            }, 'assetsGroups.assets.machineAddress.country', 'assetsGroups.assets.vendor:id,short_code']);

            return $quoteActiveVersion->assetsGroups->reduce(function (array $assets, WorldwideQuoteAssetsGroup $assetsGroup) use ($quoteAssetToOrderLineData) {
                foreach ($assetsGroup->assets as $asset) {
                    $asset->setAttribute('vendor_short_code', transform($asset->vendor, fn (Vendor $vendor) => $vendor->short_code));
                }

                return array_merge($assets, $assetsGroup->assets->map($quoteAssetToOrderLineData)->all());
            }, []);
        });

        if (empty($orderLinesData)) {
            throw new \InvalidArgumentException('At least one Pack Asset must be present on the Quote.');
        }

        $addresses = [];

        $addresses[] = $quoteActiveVersion->worldwideQuote->opportunity->primaryAccount->addresses
            ->sortByDesc('pivot.is_default')
            ->first(function (Address $address) {
                return in_array($address->address_type, [AddressType::HARDWARE, AddressType::MACHINE]);
            });

        $addresses[] = $quoteActiveVersion->worldwideQuote->opportunity->primaryAccount->addresses
            ->sortByDesc('pivot.is_default')
            ->first(function (Address $address) {
                return $address->address_type === AddressType::INVOICE;
            });

        $submitAddressDataMapper = function (Address $address): SubmitOrderAddressData {
            return new SubmitOrderAddressData([
                'address_type' => $address->address_type,
                'address_1' => $address->address_1 ?? '',
                'address_2' => $address->address_2,
                'state' => $address->state,
                'state_code' => $address->state_code,
                'country_code' => transform($address->country, fn (Country $country) => $country->iso_3166_2) ?? '',
                'city' => $address->city,
                'post_code' => $address->post_code,
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

        /** @var \App\Domain\Worldwide\Models\WorldwideQuoteAsset $firstAsset */
        $firstAsset = $quote->assets->first();

        $exchangeRateValue = $this->exchangeRateService->getRateByCurrencyCode(
            $quoteCurrency->code, now(), ManagesExchangeRates::FALLBACK_LATEST
        );

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
            'bc_company_name' => transform($company, fn (Company $company) => $company->vs_company_code),
            'company_id' => transform($company, fn (Company $company) => $company->getKey()),
            'exchange_rate' => $exchangeRateValue,
            'post_sales_id' => $salesOrder->getKey(),
            'customer_po' => $salesOrder->customer_po,
            'sales_person_name' => transform($accountManager, function (User $accountManager) {
                return sprintf('%s %s', $accountManager->first_name, $accountManager->last_name);
            }, ''),
        ]);
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

            $quotePreviewData->quote_summary->vat_number = (string) $salesOrder->vat_number;
            $quotePreviewData->quote_summary->purchase_order_number = (string) $salesOrder->customer_po;
            $quotePreviewData->quote_summary->contract_number = (string) $salesOrder->contract_number;
            $quotePreviewData->quote_summary->export_file_name = (string) $salesOrder->order_number;
            $quotePreviewData->quote_summary->sales_order_number = (string) $salesOrder->order_number;
        });
    }

    private static function excludeAssetFields(array $assetFields, array $fieldNames): array
    {
        return array_values(array_filter($assetFields, function (AssetField $assetField) use ($fieldNames) {
            return !in_array($assetField->field_name, $fieldNames, true);
        }));
    }

    public function getTemplateData(SalesOrder $salesOrder, bool $useLocalAssets = false): TemplateData
    {
        $templateSchema = $salesOrder->salesOrderTemplate->templateSchema->form_data;

        return new TemplateData([
            'first_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['first_page'] ?? []),
            'assets_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['data_pages'] ?? []),
            'payment_schedule_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['payment_schedule'] ?? []),
            'last_page_schema' => $this->templatePageSchemaToArrayOfTemplateElement($templateSchema['last_page'] ?? []),
            'template_assets' => $this->getTemplateAssets($salesOrder, $useLocalAssets),
        ]);
    }

    /**
     * @return \App\Domain\Template\DataTransferObjects\TemplateElement[]
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

    private function getTemplateAssets(SalesOrder $salesOrder, bool $useLocalAssets = false): TemplateAssets
    {
        $quote = $salesOrder->worldwideQuote;

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

        $companyImages = transform($company->image, function (Image $image) use ($flags, $company) {
            return ThumbHelper::getLogoDimensionsFromImage(
                $image,
                $company->thumbnailProperties(),
                'company_',
                $flags
            );
        }, []);

        $templateAssets = array_merge($templateAssets, $companyImages);

        /** @var Collection<\App\Domain\Vendor\Models\Vendor>|\App\Domain\Vendor\Models\Vendor[] $vendors */
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

            $logoSetX1 = array_merge($logoSetX1, Arr::wrap($vendorImages['x1'] ?? []));
            $logoSetX2 = array_merge($logoSetX2, Arr::wrap($vendorImages['x2'] ?? []));
            $logoSetX3 = array_merge($logoSetX3, Arr::wrap($vendorImages['x3'] ?? []));
        }

        $composeLogoSet = function (array $logoSet) {
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

    private function resolveCustomerVat(Company $company): ?string
    {
        if ($company->vat_type === VAT::VAT_NUMBER) {
            return $company->vat;
        }

        return $company->vat_type;
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
}
