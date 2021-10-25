<?php

namespace App\Services\WorldwideQuote;

use App\DTO\WorldwideQuote\WorldwideQuoteValidationResult;
use App\Models\Address;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\MessageBag;

class WorldwideQuoteValidator
{
    protected ValidationFactory $validationFactory;

    public function __construct(ValidationFactory $validationFactory)
    {
        $this->validationFactory = $validationFactory;
    }

    public function validateQuote(WorldwideQuote $worldwideQuote): WorldwideQuoteValidationResult
    {
        $validator = [
                CT_PACK => [$this, 'validatePackQuote'],
                CT_CONTRACT => [$this, 'validateContractQuote']
            ][$worldwideQuote->contract_type_id] ?? null;

        if (is_null($validator)) {
            throw new \RuntimeException('Unsupported Contract Type of Quote entity.');
        }

        return $validator($worldwideQuote);
    }

    protected function validateContractQuote(WorldwideQuote $quote): WorldwideQuoteValidationResult
    {
        $errorBag = new MessageBag();

        $customerDataValidator = $this->validationFactory->make(
            $this->mapQuoteCustomerToArray($quote),
            [
                'customer_name' => 'required|max:100',
                'company_reg_no' => 'present:nullable|max:50',
                'phone_no' => 'present:nullable|max:30',
                'fax_no' => 'present:nullable|max:30',
                'email' => 'required|email|max:80',
                'currency_code' => 'present|max:3',
            ],
            [
                'customer_name.required' => 'Customer Name is missing.',
                'customer_name.max' => 'Customer Name is greater than :max characters.',
                'company_reg_no.max' => 'Company Registration Number is greater than :max characters.',
                'phone_no.max' => 'Customer Phone Number is greater than :max characters.',
                'fax_no.max' => 'Customer Fax Number is greater than :max characters.',
                'email.required' => 'Customer Email is missing.',
                'email.email' => 'Customer Email is invalid.',
                'email.max' => 'Customer Email is greater than :max characters.',
                'currency_code.max' => 'Customer Currency Code is greater than :max characters.',
            ]);

        $errorBag->merge($customerDataValidator->errors());

        if ($quote->activeVersion->worldwideDistributions->isEmpty()) {

            $errorBag->add('distributor_quotes.required', 'No Distributor Quotes added.');

        } else {

            foreach ($quote->activeVersion->worldwideDistributions as $distributorQuote) {

                $distributorQuoteValidator = $this->validationFactory->make(
                    [
                        $distributorQuote->getKey() => [
                            'addresses' => $this->mapAddressesOfDistributorQuote($distributorQuote),
                            'assets' => $this->mapAssetsOfDistributorQuote($distributorQuote)
                        ]
                    ],
                    [
                        '*.addresses.invoice_address' => 'bail|required|array',
                        '*.addresses.hardware_address' => 'bail|required_without:*.addresses.software_address|array',
                        '*.addresses.software_address' => 'bail|required_without:*.addresses.hardware_address|array',
                        '*.addresses.*.country_code' => 'required|max:2',

                        '*.assets' => 'required|array',
                        '*.assets.*.service_sku' => 'required|max:100',
                        '*.assets.*.service_description' => 'required|max:100',
                        '*.assets.*.serial_number' => 'required|max:50',
                        '*.assets.*.sku' => 'required|max:50',
                        '*.assets.*.quantity' => 'required|numeric|between:0,9999999999.99',
                        '*.assets.*.product_description' => 'present|nullable|max:50',
                        '*.assets.*.unit_price' => 'required|numeric|between:0,9999999999.99',
                        '*.assets.*.buy_price' => 'required|numeric|between:0,9999999999.99',
                        '*.assets.*.machine_country_code' => 'required|max:2',
//                        '*.assets.*.distributor' => 'present|nullable|max:20',
                        '*.assets.*.vendor_name' => 'required|max:3',
                        '*.assets.*.discount_applied' => 'present|nullable|boolean',
                    ],
                    [
                        '*.addresses.invoice_address.required' => 'Invoice Address is missing.',
                        '*.addresses.hardware_address.required_without' => 'Hardware address is required when Software address is not present.',
                        '*.addresses.software_address.required_without' => 'Software address is required when Hardware address is not present.',

                        '*.addresses.invoice_address.address_1.required' => 'Invoice Address doesn\'t have a filled Address 1.',
                        '*.addresses.invoice_address.address_1.max' => 'Invoice Address has Address 1 greater than :max characters.',
                        '*.addresses.invoice_address.city.required' => 'Invoice Address doesn\'t have a filled City.',
                        '*.addresses.invoice_address.city.max' => 'Invoice Address has City greater than :max characters.',
                        '*.addresses.invoice_address.state.required' => 'Invoice Address doesn\'t have a filled State/County.',
                        '*.addresses.invoice_address.state.max' => 'Invoice Address has State greater than :max characters.',
                        '*.addresses.invoice_address.state_code.max' => 'Invoice Address has State Code greater than :max characters.',
                        '*.addresses.invoice_address.post_code.required' => 'Invoice Address doesn\'t have a filled Postal Code.',
                        '*.addresses.invoice_address.post_code.max' => 'Invoice Address has State Postal Code greater than :max characters.',
                        '*.addresses.invoice_address.country_code.required' => 'Invoice Address doesn\'t have a filled Country.',

                        '*.addresses.software_address.address_1.required' => 'Software Address doesn\'t have a filled Address 1.',
                        '*.addresses.software_address.address_1.max' => 'Software Address has Address 1 greater than :max characters.',
                        '*.addresses.software_address.city.required' => 'Software Address doesn\'t have a filled City.',
                        '*.addresses.software_address.city.max' => 'Software Address has City greater than :max characters.',
                        '*.addresses.software_address.state.required' => 'Software Address doesn\'t have a filled State/County.',
                        '*.addresses.software_address.state.max' => 'Software Address has State greater than :max characters.',
                        '*.addresses.software_address.state_code.max' => 'Software Address has State Code greater than :max characters.',
                        '*.addresses.software_address.post_code.required' => 'Software Address doesn\'t have a filled Postal Code.',
                        '*.addresses.software_address.post_code.max' => 'Software Address has State Postal Code greater than :max characters.',
                        '*.addresses.software_address.country_code.required' => 'Software Address doesn\'t have a filled Country.',

                        '*.addresses.hardware_address.address_1.required' => 'Hardware Address doesn\'t have a filled Address 1.',
                        '*.addresses.hardware_address.address_1.max' => 'Hardware Address has Address 1 greater than :max characters.',
                        '*.addresses.hardware_address.city.required' => 'Hardware Address doesn\'t have a filled City.',
                        '*.addresses.hardware_address.city.max' => 'Hardware Address has City greater than :max characters.',
                        '*.addresses.hardware_address.state.required' => 'Hardware Address doesn\'t have a filled State/County.',
                        '*.addresses.hardware_address.state.max' => 'Hardware Address has State greater than :max characters.',
                        '*.addresses.hardware_address.state_code.max' => 'Hardware Address has State Code greater than :max characters.',
                        '*.addresses.hardware_address.post_code.required' => 'Hardware Address doesn\'t have a filled Postal Code.',
                        '*.addresses.hardware_address.post_code.max' => 'Hardware Address has State Postal Code greater than :max characters.',
                        '*.addresses.hardware_address.country_code.required' => 'Hardware Address doesn\'t have a filled Country.',

                        '*.assets.required' => 'No assets are present.',
                        '*.assets.*.service_sku.required' => 'One or more assets don\'t have a service sku.',
                        '*.assets.*.service_description.required' => 'One or more assets don\'t have service level description.',
                        '*.assets.*.service_description.max' => 'One or more assets have service level description greater than :max characters.',
                        '*.assets.*.product_description.required' => 'One or more assets don\'t have product description.',
                        '*.assets.*.product_description.max' => 'One or more assets have product description greater than :max characters.',
                        '*.assets.*.serial_number.required' => 'One or more assets don\'t have serial number.',
                        '*.assets.*.sku.required' => 'One or more assets don\'t have sku number.',
                        '*.assets.*.quantity.required' => 'One or more assets don\'t have quantity.',
                        '*.assets.*.unit_price.required' => 'One or more assets don\'t have price.',
                        '*.assets.*.buy_price.required' => 'One or more assets don\'t have price.',
                        '*.assets.*.machine_country_code.required' => 'One or more assets don\'t have machine country.',
                        '*.assets.*.vendor_name.required' => 'One or more assets don\'t have a vendor name.',
                        '*.assets.*.vendor_name.max' => 'One or more assets have an invalid vendor name.',
                        '*.assets.*.distributor.max' => 'One or more suppliers have name greater than 20 characters.'
                    ]
                );

                $distributorQuoteErrors = $distributorQuoteValidator->getMessageBag()->setFormat(sprintf("Supplier '%s': :message", $distributorQuote->opportunitySupplier->supplier_name));

                $errorBag->merge($distributorQuoteErrors->all());

            }

        }

        return new WorldwideQuoteValidationResult(
            $errorBag->isEmpty(),
            $errorBag
        );
    }

    protected function validatePackQuote(WorldwideQuote $quote): WorldwideQuoteValidationResult
    {

        $validator = $this->validationFactory->make([
            'customer' => $this->mapQuoteCustomerToArray($quote),
            'addresses' => $this->mapAddressesOfPackQuote($quote),
            'assets' => $this->mapQuoteAssetsToArray($quote),
        ], [
            'customer.customer_name' => 'required|max:100',
            'customer.company_reg_no' => 'present:nullable|max:50',
            'customer.phone_no' => 'present:nullable|max:30',
            'customer.fax_no' => 'present:nullable|max:30',
            'customer.email' => 'required|email|max:80',
            'customer.currency_code' => 'present|max:3',

            'addresses' => 'required|array',
            'addresses.invoice_address' => 'required|array',
            'addresses.hardware_address' => 'required_without:addresses.software_address|array',
            'addresses.software_address' => 'required_without:addresses.hardware_address|array',
            'addresses.*.country_code' => 'required|max:2',

            'assets' => 'required|array',
            'assets.*.service_sku' => 'required|max:100',
            'assets.*.service_description' => 'required|max:100',
            'assets.*.serial_number' => 'required|max:50',
            'assets.*.sku' => 'required|max:50',
            'assets.*.quantity' => 'required|numeric|between:0,9999999999.99',
            'assets.*.product_description' => 'present:nullable|max:50',
            'assets.*.unit_price' => 'required|numeric|between:0,9999999999.99',
            'assets.*.buy_price' => 'required|numeric|between:0,9999999999.99',
            'assets.*.machine_country_code' => 'required|max:2',
//            'assets.*.distributor' => 'present|nullable|max:20',
            'assets.*.vendor_name' => 'required|max:3',
            'assets.*.discount_applied' => 'present|nullable|boolean',
        ], [
            'customer.customer_name.required' => 'Customer Name is missing.',
            'customer.customer_name.max' => 'Customer Name is greater than :max characters.',
            'customer.company_reg_no.max' => 'Company Registration Number is greater than :max characters.',
            'customer.phone_no.max' => 'Customer Phone Number is greater than :max characters.',
            'customer.fax_no.max' => 'Customer Fax Number is greater than :max characters.',
            'customer.email.required' => 'Customer Email is missing.',
            'customer.email.email' => 'Customer Email is invalid.',
            'customer.email.max' => 'Customer Email is greater than :max characters.',
            'customer.currency_code.max' => 'Customer Currency Code is greater than :max characters.',

            'addresses.invoice_address.required' => 'Invoice Address is missing.',
            'addresses.hardware_address.required_without' => 'Hardware address is required when Software address is not present.',
            'addresses.software_address.required_without' => 'Software address is required when Hardware address is not present.',

            'addresses.invoice_address.address_1.required' => 'Invoice Address doesn\'t have a filled Address 1.',
            'addresses.invoice_address.address_1.max' => 'Invoice Address has Address 1 greater than :max characters.',
            'addresses.invoice_address.city.required' => 'Invoice Address doesn\'t have a filled City.',
            'addresses.invoice_address.city.max' => 'Invoice Address has City greater than :max characters.',
            'addresses.invoice_address.state.required' => 'Invoice Address doesn\'t have a filled State/County.',
            'addresses.invoice_address.state.max' => 'Invoice Address has State greater than :max characters.',
            'addresses.invoice_address.state_code.max' => 'Invoice Address has State Code greater than :max characters.',
            'addresses.invoice_address.post_code.required' => 'Invoice Address doesn\'t have a filled Postal Code.',
            'addresses.invoice_address.post_code.max' => 'Invoice Address has State Postal Code greater than :max characters.',
            'addresses.invoice_address.country_code.required' => 'Invoice Address doesn\'t have a filled Country.',

            'addresses.software_address.address_1.required' => 'Software Address doesn\'t have a filled Address 1.',
            'addresses.software_address.address_1.max' => 'Software Address has Address 1 greater than :max characters.',
            'addresses.software_address.city.required' => 'Software Address doesn\'t have a filled City.',
            'addresses.software_address.city.max' => 'Software Address has City greater than :max characters.',
            'addresses.software_address.state.required' => 'Software Address doesn\'t have a filled State/County.',
            'addresses.software_address.state.max' => 'Software Address has State greater than :max characters.',
            'addresses.software_address.state_code.max' => 'Software Address has State Code greater than :max characters.',
            'addresses.software_address.post_code.required' => 'Software Address doesn\'t have a filled Postal Code.',
            'addresses.software_address.post_code.max' => 'Software Address has State Postal Code greater than :max characters.',
            'addresses.software_address.country_code.required' => 'Software Address doesn\'t have a filled Country.',

            'addresses.hardware_address.address_1.required' => 'Hardware Address doesn\'t have a filled Address 1.',
            'addresses.hardware_address.address_1.max' => 'Hardware Address has Address 1 greater than :max characters.',
            'addresses.hardware_address.city.required' => 'Hardware Address doesn\'t have a filled City.',
            'addresses.hardware_address.city.max' => 'Hardware Address has City greater than :max characters.',
            'addresses.hardware_address.state.required' => 'Hardware Address doesn\'t have a filled State/County.',
            'addresses.hardware_address.state.max' => 'Hardware Address has State greater than :max characters.',
            'addresses.hardware_address.state_code.max' => 'Hardware Address has State Code greater than :max characters.',
            'addresses.hardware_address.post_code.required' => 'Hardware Address doesn\'t have a filled Postal Code.',
            'addresses.hardware_address.post_code.max' => 'Hardware Address has State Postal Code greater than :max characters.',
            'addresses.hardware_address.country_code.required' => 'Hardware Address doesn\'t have a filled Country.',

            'assets.*.service_sku.required' => 'One or more assets don\'t have a service sku.',
            'assets.*.service_description.required' => 'One or more assets don\'t have service level description.',
            'assets.*.service_description.max' => 'One or more assets have service level description greater than :max characters.',
            'assets.*.product_description.required' => 'One or more assets don\'t have product description.',
            'assets.*.product_description.max' => 'One or more assets have product description greater than :max characters.',
            'assets.*.serial_number.required' => 'One or more assets don\'t have serial number.',
            'assets.*.sku.required' => 'One or more assets don\'t have sku number.',
            'assets.*.quantity.required' => 'One or more assets don\'t have quantity.',
            'assets.*.unit_price.required' => 'One or more assets don\'t have price.',
            'assets.*.buy_price.required' => 'One or more assets don\'t have price.',
            'assets.*.machine_country_code.required' => 'One or more assets don\'t have machine country.',
            'assets.*.vendor_name.required' => 'One or more assets don\'t have a vendor name.',
            'assets.*.vendor_name.max' => 'One or more assets have an invalid vendor name.',
            'assets.*.distributor.max' => 'One or more suppliers have name greater than 20 characters.'
        ]);

        return new WorldwideQuoteValidationResult(
            !$validator->fails(),
            $validator->errors()
        );
    }

    private function mapQuoteCustomerToArray(WorldwideQuote $quote): array
    {
        $customer = $quote->opportunity->primaryAccount;

        return [
            'customer_name' => $customer->name,
            'company_reg_no' => null,
            'vat_reg_no' => $customer->vat,
            'phone_no' => $customer->phone,
            'fax_no' => null,
            'email' => $customer->email,
            'currency_code' => transform($quote->activeVersion->quoteCurrency, fn(Currency $currency) => $currency->code),
        ];
    }

    private function mapAssetsOfDistributorQuote(WorldwideDistribution $distributorQuote): array
    {
        /** @var Vendor $vendor */
        $vendor = $distributorQuote->vendors->first(null, new Vendor());

        if ($distributorQuote->use_groups) {
            $distributorQuote->load(['rowsGroups' => function (Relation $relation) {
                $relation->where('is_selected', true);
            }, 'rowsGroups.rows']);

            return $distributorQuote->rowsGroups->reduce(function (array $rows, DistributionRowsGroup $rowsGroup) use ($vendor, $distributorQuote) {
                $rowsOfGroup = array_map(fn(MappedRow $row) => [
                    'service_sku' => $row->service_sku,
                    'service_description' => $row->service_level_description,
                    'serial_number' => $row->serial_no,
                    'sku' => $row->product_no,
                    'product_description' => $row->description,
                    'unit_price' => $row->price,
                    'buy_price' => $row->price,
                    'quantity' => $row->qty,
                    'machine_country_code' => transform($distributorQuote->country, fn(Country $country) => $country->iso_3166_2),
                    'vendor_name' => $vendor->short_code,
                    'distributor' => $distributorQuote->opportunitySupplier->supplier_name,
                    'discount_applied' => null,
                ], $rowsGroup->rows->all());

                return array_merge($rows, $rowsOfGroup);
            }, []);
        }

        $distributorQuote->load(['mappedRows' => function (Relation $relation) {
            $relation->where('is_selected', true);
        }]);

        return array_map(fn(MappedRow $row) => [
            'service_sku' => $row->service_sku,
            'service_description' => $row->service_level_description,
            'serial_number' => $row->serial_no,
            'sku' => $row->product_no,
            'product_description' => $row->description,
            'unit_price' => $row->price,
            'buy_price' => $row->price,
            'quantity' => $row->qty,
            'machine_country_code' => transform($distributorQuote->country, fn(Country $country) => $country->iso_3166_2),
            'vendor_name' => $vendor->short_code,
            'distributor' => $distributorQuote->opportunitySupplier->supplier_name,
            'discount_applied' => null,
        ], $distributorQuote->mappedRows->all());
    }

    private function mapQuoteAssetsToArray(WorldwideQuote $quote): array
    {
        if ($quote->contract_type_id === CT_CONTRACT) {
            return $quote->activeVersion->worldwideDistributions->reduce(function (array $assets, WorldwideDistribution $distributorQuote) {

                $distributorQuoteAssets = value(function () use ($distributorQuote): array {
                    /** @var Vendor $vendor */
                    $vendor = $distributorQuote->vendors->first(null, new Vendor());

                    if ($distributorQuote->use_groups) {
                        $distributorQuote->load(['rowsGroups' => function (Relation $relation) {
                            $relation->where('is_selected', true);
                        }, 'rowsGroups.rows']);

                        return $distributorQuote->rowsGroups->reduce(function (array $rows, DistributionRowsGroup $rowsGroup) use ($vendor, $distributorQuote) {
                            $rowsOfGroup = $rowsGroup->rows->map(fn(MappedRow $row) => [
                                'service_sku' => $row->service_sku,
                                'service_description' => $row->service_level_description,
                                'serial_number' => $row->serial_no,
                                'sku' => $row->product_no,
                                'product_description' => $row->description,
                                'unit_price' => $row->price,
                                'buy_price' => $row->price,
                                'quantity' => $row->qty,
                                'machine_country_code' => transform($distributorQuote->country, fn(Country $country) => $country->iso_3166_2),
                                'vendor_name' => $vendor->short_code,
                                'distributor' => $distributorQuote->opportunitySupplier->supplier_name,
                                'discount_applied' => null,
                            ])->all();

                            return array_merge($rows, $rowsOfGroup);
                        }, []);
                    }

                    $distributorQuote->load(['mappedRows' => function (Relation $relation) {
                        $relation->where('is_selected', true);
                    }]);

                    return $distributorQuote->mappedRows->map(fn(MappedRow $row) => [
                        'service_sku' => $row->service_sku,
                        'service_description' => $row->service_level_description,
                        'serial_number' => $row->serial_no,
                        'sku' => $row->product_no,
                        'product_description' => $row->description,
                        'unit_price' => $row->price,
                        'buy_price' => $row->price,
                        'quantity' => $row->qty,
                        'machine_country_code' => transform($distributorQuote->country, fn(Country $country) => $country->iso_3166_2),
                        'vendor_name' => $vendor->short_code,
                        'distributor' => $distributorQuote->opportunitySupplier->supplier_name,
                        'discount_applied' => null,
                    ])->all();
                });

                return array_merge($assets, $distributorQuoteAssets);

            }, []);
        }

        if ($quote->contract_type_id === CT_PACK) {
            $quote->load(['assets' => function (Relation $relation) {
                $relation->where('is_selected', true);
            }]);

            return $quote->activeVersion->assets->map(fn(WorldwideQuoteAsset $asset) => [
                'service_sku' => $asset->service_sku,
                'service_description' => $asset->service_level_description,
                'serial_number' => $asset->serial_no,
                'sku' => $asset->sku,
                'product_description' => $asset->product_name,
                'unit_price' => $asset->price,
                'buy_price' => $asset->price,
                'quantity' => 1,
                'machine_country_code' => transform($asset->machineAddress, function (Address $address): string {
                    if (!is_null($address->country)) {
                        return $address->country->iso_3166_2;
                    }

                    return '';
                }),
                'vendor_name' => $asset->vendor_short_code,
                'distributor' => null,
                'discount_applied' => null,
            ])->all();
        }

        throw new \RuntimeException("Unsupported Contract Type of the Quote entity.");
    }

    private function mapAddressesOfPackQuote(WorldwideQuote $quote): array
    {
        $addressDictionary = $quote->activeVersion->addresses->keyBy('address_type');

        $addresses = [
            'invoice_address' => $addressDictionary['Invoice'] ?? null,
            'hardware_address' => $addressDictionary['Hardware'] ?? $addressDictionary['Machine'] ?? null,
            'software_address' => $addressDictionary['Software'] ?? null,
        ];

        return array_map(function (Address $address) {
            return self::addressToArray($address);
        }, array_filter($addresses));
    }

    private function mapAddressesOfDistributorQuote(WorldwideDistribution $distributorQuote): array
    {
        $addressDictionary = $distributorQuote->addresses->keyBy('address_type');

        $addresses = [
            'invoice_address' => $addressDictionary['Invoice'] ?? null,
            'hardware_address' => $addressDictionary['Hardware'] ?? $addressDictionary['Machine'] ?? null,
            'software_address' => $addressDictionary['Software'] ?? null,
        ];

        return array_map(function (Address $address) {
            return self::addressToArray($address);
        }, array_filter($addresses));
    }

    private static function addressToArray(Address $address): array
    {
        return [
            'address_type' => $address->address_type,
            'address_1' => $address->address_1,
            'address_2' => $address->address_2,
            'city' => $address->city,
            'state' => $address->state,
            'state_code' => null,
            'post_code' => $address->post_code,
            'country_code' => transform($address->country, fn(Country $country) => $country->iso_3166_2),
        ];
    }
}
