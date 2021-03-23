<?php

namespace App\Services\WorldwideQuote;

use App\DTO\WorldwideQuote\WorldwideQuoteValidationResult;
use App\Models\Address;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\MappedRow;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\Eloquent\Relations\Relation;

class WorldwideQuoteValidator
{
    protected ValidationFactory $validationFactory;

    public function __construct(ValidationFactory $validationFactory)
    {
        $this->validationFactory = $validationFactory;
    }

    public function validateQuote(WorldwideQuote $worldwideQuote): WorldwideQuoteValidationResult
    {
        $validator = $this->validationFactory->make([
            'customer' => $this->mapQuoteCustomerToArray($worldwideQuote),
            'addresses' => $this->mapQuoteAddressesToArray($worldwideQuote),
            'assets' => $this->mapQuoteAssetsToArray($worldwideQuote),
        ], [
            "customer.customer_name" => "required|max:100",
            "customer.company_reg_no" => "present:nullable|max:50",
//            "customer.vat_reg_no" => "required|max:20",
            "customer.phone_no" => "present:nullable|max:30",
            "customer.fax_no" => "present:nullable|max:30",
            "customer.email" => "required|email|max:80",
            "customer.currency_code" => "present|max:3",

            "addresses" => "required|array",
            "addresses.*.address_type" => "required|max:30",
            "addresses.*.address_1" => "required|max:100",
            "addresses.*.address_2" => "present:nullable|max:50",
            "addresses.*.city" => "required|max:30",
            "addresses.*.state" => "present:nullable|max:30",
            "addresses.*.state_code" => "present:nullable|max:2",
            "addresses.*.post_code" => "required|max:20",
            "addresses.*.country_code" => "required|max:2",

            "assets" => "required|array",
            "assets.*.service_sku" => "required|max:100",
            "assets.*.service_description" => "required|max:100",
            "assets.*.serial_number" => "required|max:50",
            "assets.*.sku" => "required|max:50",
            "assets.*.quantity" => "required|numeric|between:0,9999999999.99",
            "assets.*.product_description" => "present:nullable|max:50",
            "assets.*.unit_price" => "required|numeric|between:0,9999999999.99",
            "assets.*.buy_price" => "required|numeric|between:0,9999999999.99",
            "assets.*.machine_country_code" => "required|max:2",
            "assets.*.distributor" => "present:nullable|max:20",
            "assets.*.vendor_name" => "required|max:3",
            "assets.*.discount_applied" => "present|nullable|boolean",
        ], [
            'assets.*.service_sku.required' => 'One or more assets don\'t have a service sku.',
            'assets.*.service_description.required' => 'One or more assets don\'t have service level description.',
            'assets.*.service_description.max' => 'One or more assets have service level description greater than 50 characters.',
            'assets.*.product_description.required' => 'One or more assets don\'t have product description.',
            'assets.*.product_description.max' => 'One or more assets have product description greater than 50 characters.',
            'assets.*.serial_number.required' => 'One or more assets don\'t have serial number.',
            'assets.*.sku.required' => 'One or more assets don\'t have sku number.',
            'assets.*.quantity.required' => 'One or more assets don\'t have quantity.',
            'assets.*.unit_price.required' => 'One or more assets don\'t have price.',
            'assets.*.buy_price.required' => 'One or more assets don\'t have price.',
            'assets.*.machine_country_code.required' => 'One or more assets don\'t have machine country.',
            'assets.*.vendor_name.required' => 'One or more assets don\'t have a vendor name.',
            'assets.*.vendor_name.max' => 'One or more assets have an invalid vendor name.',
            'assets.*.distributor' => 'One or more suppliers have name greater than 20 characters.'
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
            'currency_code' => transform($quote->quoteCurrency, fn(Currency $currency) => $currency->code),
        ];
    }

    private function mapQuoteAssetsToArray(WorldwideQuote $quote): array
    {
        if ($quote->contract_type_id === CT_CONTRACT) {
            return $quote->worldwideDistributions->reduce(function (array $assets, WorldwideDistribution $distributorQuote) {

                // TODO: add distinction when groups of rows are used.
                $distributorQuote->load(['mappedRows' => function (Relation $relation) {
                    $relation->where('is_selected', true);
                }]);

                /** @var Vendor $vendor */
                $vendor = $distributorQuote->vendors->first(null, new Vendor());

                $distributorQuoteAssets = $distributorQuote->mappedRows->map(fn(MappedRow $row) => [
                    'service_sku' => $row->service_sku,
                    'service_description' => $row->service_level_description,
                    'serial_number' => $row->serial_no,
                    'sku' => $row->product_no,
                    'product_description' => $row->description,
                    'unit_price' => $row->price,
                    'buy_price' => $row->price,
                    'quantity' => $row->qty,
                    'machine_country_code' => transform($distributorQuote->country, fn (Country $country) => $country->iso_3166_2),
                    'vendor_name' => $vendor->short_code,
                    'distributor' => $distributorQuote->opportunitySupplier->supplier_name,
                    'discount_applied' => null,
                ])->all();

                return array_merge($assets, $distributorQuoteAssets);

            }, []);
        }

        if ($quote->contract_type_id === CT_PACK) {
            $quote->load(['assets' => function (Relation $relation) {
                $relation->where('is_selected', true);
            }]);

            return $quote->assets->map(fn(WorldwideQuoteAsset $asset) => [
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

    private function mapQuoteAddressesToArray(WorldwideQuote $quote): array
    {
        $addressToArray = static function (Address $address): array {
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
        };

        if ($quote->contract_type_id === CT_CONTRACT) {

            return $quote->worldwideDistributions->reduce(function (array $addresses, WorldwideDistribution $distributorQuote) use ($addressToArray) {

                $distributorQuoteAddresses = $distributorQuote->addresses->map($addressToArray)->all();

                return array_merge($addresses, $distributorQuoteAddresses);

            }, []);

        }

        if ($quote->contract_type_id === CT_PACK) {

            return $quote->opportunity->addresses->map($addressToArray)->all();

        }

        throw new \RuntimeException("Unsupported Contract Type of the Quote entity.");
    }
}
