<?php

namespace App\Http\Requests\Asset;

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
            'asset_id' => 'nullable|uuid',
            'vendor_id' => 'uuid',
            'serial_number' => 'filled|string',
        ];
    }

    public function validated()
    {
        return [
            ['id', '!=', $this->asset_id],
            ['vendor_id', '=', $this->vendor_id],
            ['serial_number', '=', $this->serial_number],
        ];
    }
}
