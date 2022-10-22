<?php

namespace App\Http\Requests\Asset;

use App\Models\Asset;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

class Uniqueness extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'nullable|uuid',
            'vendor_id' => 'uuid',
            'serial_number' => 'filled|string',
            'product_number' => 'nullable|filled|string',
        ];
    }

    public function getIgnoreModelKey(): ?string
    {
        return $this->input('id');
    }

    public function getOwnerKey(): ?string
    {
        if (filled($this->getIgnoreModelKey())) {
            return Asset::query()->whereKey($this->getIgnoreModelKey())->value('user_id');
        }

        return $this->user()->getKey();
    }

    public function getVendorKey(): ?string
    {
        return $this->input('vendor_id');
    }

    public function getSerialNumber(): ?string
    {
        return $this->input('serial_number');
    }

    public function getProductNumber(): ?string
    {
        return $this->input('product_number');
    }
}
