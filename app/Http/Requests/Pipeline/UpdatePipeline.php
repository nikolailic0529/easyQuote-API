<?php

namespace App\Http\Requests\Pipeline;

use App\DTO\Pipeline\UpdatePipelineData;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeline\PipelineStage;
use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePipeline extends FormRequest
{
    protected ?UpdatePipelineData $updatePipelineData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'space_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Space::class, 'id'),
            ],
            'pipeline_name' => [
                'bail', 'required', 'string', 'max:191',
            ],
            'pipeline_stages' => [
                'bail', 'present', 'array',
            ],
            'pipeline_stages.*.id' => [
                'bail', 'present', 'nullable', 'uuid',
                Rule::exists(PipelineStage::class, 'id')->where('pipeline_id', $this->getPipelineModel()->getKey()),
            ],
            'pipeline_stages.*.stage_name' => [
                'bail', 'string', 'max:191',
            ],
            'pipeline_stages.*.stage_percentage' => [
                'bail', 'required', 'numeric', 'min:0', 'max:100',
            ],
        ];
    }

    public function getPipelineModel(): Pipeline
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('pipeline');
    }

    public function getUpdatePipelineData(): UpdatePipelineData
    {
        return $this->updatePipelineData ??= value(function (): UpdatePipelineData {
            $pipelineStages = array_map(function (array $stage) {
                static $order = 1;

                return [
                    'stage_id' => $stage['id'],
                    'stage_name' => $stage['stage_name'],
                    'stage_order' => $order++,
                    'stage_percentage' => (float)$stage['stage_percentage'],
                ];
            }, $this->input('pipeline_stages'));

            return new UpdatePipelineData([
                'space_id' => $this->input('space_id'),
                'pipeline_name' => $this->input('pipeline_name'),
                'pipeline_stages' => $pipelineStages,
            ]);
        });
    }
}
