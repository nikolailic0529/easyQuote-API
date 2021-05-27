<?php

namespace App\Http\Requests\OpportunityForm;

use App\DTO\OpportunityForm\CreateOpportunityFormData;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\Pipeline\Pipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityForm extends FormRequest
{
    protected ?CreateOpportunityFormData $createOpportunityFormData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'pipeline_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Pipeline::class, 'id')->whereNull('deleted_at'),
                Rule::unique(OpportunityForm::class, 'pipeline_id')->whereNull('deleted_at')
            ]
        ];
    }

    public function messages()
    {
        return [
            'pipeline_id.unique' => 'The chosen Pipeline already has Opportunity Form.',
        ];
    }

    public function getCreateOpportunityFormData(): CreateOpportunityFormData
    {
        return $this->createOpportunityFormData ??= new CreateOpportunityFormData([
            'pipeline_id' => $this->input('pipeline_id'),
        ]);
    }
}
