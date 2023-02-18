<?php

namespace App\Domain\Template\Requests\OpportunityForm;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Template\DataTransferObjects\UpdateOpportunityFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOpportunityFormRequest extends FormRequest
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
                Rule::unique(\App\Domain\Worldwide\Models\OpportunityForm::class, 'pipeline_id')->whereNull('deleted_at')->ignoreModel($this->getOpportunityFormModel()),
            ],
        ];
    }

    public function getOpportunityFormModel(): \App\Domain\Worldwide\Models\OpportunityForm
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
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
            'pipeline_id' => $this->input('pipeline_id'),
        ]);
    }
}
