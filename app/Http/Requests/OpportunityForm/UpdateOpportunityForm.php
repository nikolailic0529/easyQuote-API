<?php

namespace App\Http\Requests\OpportunityForm;

use App\DTO\OpportunityForm\UpdateOpportunityFormData;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\Pipeline\Pipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOpportunityForm extends FormRequest
{
    protected ?UpdateOpportunityFormData $updateOpportunityFormData = null;

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
                Rule::unique(OpportunityForm::class, 'pipeline_id')->whereNull('deleted_at')->ignoreModel($this->getOpportunityFormModel()),
            ]
        ];
    }

    public function getOpportunityFormModel(): OpportunityForm
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('opportunity_form');
    }

    public function messages()
    {
        return [
            'pipeline_id.unique' => 'The chosen Pipeline already has Opportunity Form.',
        ];
    }

    public function getUpdateOpportunityFormData(): UpdateOpportunityFormData
    {
        return $this->updateOpportunityFormData ??= new UpdateOpportunityFormData([
            'pipeline_id' => $this->input('pipeline_id')
        ]);
    }
}
