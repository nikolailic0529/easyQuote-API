<?php

namespace App\Domain\Vendor\Requests;

use App\Domain\Vendor\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                Rule::unique(Vendor::class)->withoutTrashed(),
            ],
            'short_code' => [
                'required',
                'string',
                'min:2',
                Rule::unique(Vendor::class)->withoutTrashed(),
            ],
            'logo' => [
                'image',
                'max:2048',
            ],
            'countries' => [
                'required',
                'array',
            ],
            'countries.*' => [
                'required',
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
