<?php

namespace App\Http\Requests\Contract;

use App\Models\Quote\Contract;
use App\Models\Template\TemplateField;
use Illuminate\Foundation\Http\FormRequest;

class ShowContractState extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function loadContractAttributes(Contract $contract): Contract
    {
        return tap($contract, function (Contract $contract) {
            $templateFields = TemplateField::with('templateFieldType')->orderBy('order')->get();
            
            $contract->contractTemplate->setAttribute('template_fields', $templateFields);
        });
    }
}
