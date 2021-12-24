<?php

namespace App\Http\Requests\Quote;

use App\Models\Quote\Contract;
use App\Models\Template\TemplateField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class StoreContractStateRequest extends FormRequest
{
    use HandlesAuthorization;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'closing_date' => 'nullable|date_format:Y-m-d',
            'additional_notes' => 'nullable|string|max:20000'
        ];
    }

    public function messages()
    {
        return [];
    }

    public function loadContractAttributes(Contract $contract): Contract
    {
        return tap($contract, function (Contract $contract) {
            $templateFields = TemplateField::with('templateFieldType')->orderBy('order')->get();
            
            $contract->contractTemplate->setAttribute('template_fields', $templateFields);
        });
    }
}
