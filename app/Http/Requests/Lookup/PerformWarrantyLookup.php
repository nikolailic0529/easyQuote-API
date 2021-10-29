<?php

namespace App\Http\Requests\Lookup;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\Intl\Countries;

class PerformWarrantyLookup extends FormRequest
{
    const VENDORS_REQ_SKU = ['LEN', 'IBM'];
    const VENDORS_REQ_COUNTRY = ['HPE'];

    protected ?Vendor $vendor = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $vendors = array_keys(config('services.vs.service_routes'));
        

        return [
            'vendor_id' => ['required', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')->whereIn('short_code', $vendors)],
            'serial_number' => ['required', 'string'],
            'product_number' => [
                Rule::requiredIf(function () {
                    return in_array($this->getVendor()->short_code, static::VENDORS_REQ_SKU);
                }),
                'nullable',
                'string'
            ],
            'country_code' => [
                Rule::requiredIf(function () {
                    return in_array($this->getVendor()->short_code, static::VENDORS_REQ_COUNTRY);
                }),
                'nullable',
                'string',
                Rule::in(Countries::getCountryCodes())
            ]
        ];
    }

    public function getVendor(): Vendor
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->vendor ??= Vendor::query()->findOrFail($this->input('vendor_id'));
    }

    public function getVendorCode(): ?string
    {
        return $this->getVendor()->short_code;
    }

    public function getSerialNumber(): ?string
    {
        return $this->input('serial_number');
    }

    public function getProductNumber(): ?string
    {
        return $this->input('product_number');
    }

    public function getCountryCode(): ?string
    {
        return $this->input('country_code');
    }
}
