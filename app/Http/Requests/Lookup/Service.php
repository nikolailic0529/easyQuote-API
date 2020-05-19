<?php

namespace App\Http\Requests\Lookup;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Service extends FormRequest
{
    protected const VENDORS_REQ_SKU = ['LEN', 'IBM'];

    protected ?Vendor $vendor = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $vendor = $this->vendor();
        $vendors = array_keys(config('services.vs.service_routes'));
        

        return [
            'vendor_id' => ['required', Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')->whereIn('short_code', $vendors)],
            'serial_number' => ['required', 'string'],
            'product_number' => [Rule::requiredIf(fn () => in_array($vendor->short_code, static::VENDORS_REQ_SKU))],
        ];
    }

    public function vendor(): Vendor
    {
        if (isset($this->vendor)) {
            return $this->vendor;
        }

        return $this->vendor = Vendor::findOrFail($this->vendor_id);
    }
}
