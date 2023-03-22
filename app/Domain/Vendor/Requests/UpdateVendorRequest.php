<?php

namespace App\Domain\Vendor\Requests;

use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'min:3',
                Rule::unique(Vendor::class)
                    ->ignore($this->route('vendor'))
                    ->withoutTrashed(),
            ],
            'short_code' => [
                'string',
                'min:2',
                Rule::unique('vendors')
                    ->ignore($this->route('vendor'))
                    ->withoutTrashed(),
            ],
            'logo' => [
                'image',
                'max:2048',
            ],
            'countries' => [
                'array',
            ],
            'countries.*' => [
                'uuid',
                'exists:countries,id',
            ],
        ];
    }

    public function validated(): array
    {
        $validated = parent::validated();

        $short_code = strtoupper(data_get($validated, 'short_code'));

        return array_merge($validated, compact('short_code'));
    }
}
