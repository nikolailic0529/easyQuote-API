<?php

namespace App\Http\Requests\Opportunities;

use Illuminate\Foundation\Http\FormRequest;

class BatchUpload extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'opportunities_file' => [
                'bail', 'required', 'file', 'mimes:xlsx', 'max:10000'
            ],
            'accounts_data_file' => [
                'bail', 'required', 'file', 'mimes:xlsx', 'max:10000'
            ],
            'account_contacts_file' => [
                'bail', 'required', 'file', 'mimes:xlsx', 'max:10000'
            ]
        ];
    }
}
