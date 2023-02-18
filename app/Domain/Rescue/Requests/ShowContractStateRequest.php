<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Rescue\Models\Contract;
use App\Domain\Template\Models\TemplateField;
use Illuminate\Foundation\Http\FormRequest;

class ShowContractStateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
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
