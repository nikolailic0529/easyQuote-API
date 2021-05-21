<?php

namespace App\Http\Requests\Pipeline;

use App\DTO\Pipeline\CreatePipelineData;
use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePipeline extends FormRequest
{
    protected ?CreatePipelineData $createPipelineData = null;

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
                Rule::exists(Space::class, 'id')
            ],
            'pipeline_name' => [
                'bail', 'required', 'string', 'max:191'
            ],
            'pipeline_stages' => [
                'bail', 'present', 'array'
            ],
            'pipeline_stages.*.stage_name' => [
                'bail', 'string', 'max:191'
            ],
        ];
    }

    public function getCreatePipelineData(): CreatePipelineData
    {
        return $this->createPipelineData ??= value(function (): CreatePipelineData {
            $pipelineStages = array_map(function (array $stage) {
                static $order = 1;

                return [
                    'stage_id' => null,
                    'stage_name' => $stage['stage_name'],
                    'stage_order' => $order++,
                ];
            }, $this->input('pipeline_stages'));

            return new CreatePipelineData([
                'space_id' => $this->input('space_id'),
                'pipeline_name' => $this->input('pipeline_name'),
                'pipeline_stages' => $pipelineStages
            ]);
        });
    }
}
