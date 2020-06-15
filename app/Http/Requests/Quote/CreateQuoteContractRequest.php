<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateQuoteContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\Quote\Quote */
        $quote = $this->route('submitted');

        /** @var \App\Models\User */
        $user = $this->user();
        
        if ($quote->contract->exists) {
            return $user->can('update', $quote->contract);
        }

        return $user->can('createContract', $quote);
    }
    
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
            ],
            'service_agreement_id' => 'string|max:300|min:2'
        ];
    }
}
