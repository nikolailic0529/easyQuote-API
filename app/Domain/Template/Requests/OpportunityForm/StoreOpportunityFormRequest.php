<?php

namespace App\Domain\Template\Requests\OpportunityForm;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Template\DataTransferObjects\CreateOpportunityFormData;
use App\Domain\Worldwide\Models\OpportunityForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityFormRequest extends FormRequest
{
    protected ?\App\Domain\Template\DataTransferObjects\CreateOpportunityFormData $createOpportunityFormData = null;

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
                Rule::unique(OpportunityForm::class, 'pipeline_id')->whereNull('deleted_at'),
            ],
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
