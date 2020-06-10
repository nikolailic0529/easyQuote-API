<?php

namespace App\Http\Requests\Asset;

use App\Models\Asset;
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
        ];
    }

    public function validated()
    {
        /**
         * If the asset id is present we'll assume use asset user.
         * Otherwise we will use authenticated user.
         */
        $userId = $this->filled('id') ? Asset::whereKey($this->id)->value('user_id') : auth()->id();

        return [
            ['id', '!=', $this->id],
            ['user_id', '=', $userId],
            ['vendor_id', '=', $this->vendor_id],
            ['serial_number', '=', $this->serial_number],
        ];
    }
}
