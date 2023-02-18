<?php

namespace App\Domain\Rescue\Requests;

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
        /** @var \App\Domain\Rescue\Models\Quote */
        $quote = $this->route('submitted');

        /** @var \App\Domain\User\Models\User */
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
                Rule::exists('contract_templates', 'id')->whereNull('deleted_at'),
            ],
            'service_agreement_id' => 'string|max:300|min:2',
        ];
    }
}
