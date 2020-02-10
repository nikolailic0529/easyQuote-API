<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateQuoteContractRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contract_template_id' => [
                'required',
                'string',
                'uuid',
                Rule::exists('quote_templates', 'id')->whereNull('deleted_at')->where('type', QT_TYPE_CONTRACT)
            ]
        ];
    }
}
