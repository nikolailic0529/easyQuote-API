<?php

namespace App\Http\Requests\Opportunity;

use App\DTO\Opportunity\SetStageOfOpportunityData;
use App\Models\Pipeline\PipelineStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetStageOfOpportunity extends FormRequest
{
    protected readonly ?SetStageOfOpportunityData $setStageOfOpportunityData;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'order_in_stage' => ['bail', 'required', 'integer', 'min:0', 'max:'.(string)(1 << 32)],
            'stage_id' => ['bail', 'required', 'uuid', Rule::exists(PipelineStage::class, 'id')->withoutTrashed()],
        ];
    }

    public function getSetStageOfOpportunityData(): SetStageOfOpportunityData
    {
        return $this->setStageOfOpportunityData ??= new SetStageOfOpportunityData(
            $this->only(['order_in_stage', 'stage_id'])
        );
    }
}
