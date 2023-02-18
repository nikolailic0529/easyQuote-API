<?php

namespace App\Domain\Pipeline\Requests;

use App\Domain\Pipeline\DataTransferObjects\CreatePipelineData;
use App\Domain\Space\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePipelineRequest extends FormRequest
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
                Rule::exists(Space::class, 'id'),
            ],
            'pipeline_name' => [
                'bail', 'required', 'string', 'max:191',
            ],
            'pipeline_stages' => [
                'bail', 'present', 'array',
            ],
            'pipeline_stages.*.stage_name' => [
                'bail', 'string', 'max:191',
            ],
            'pipeline_stages.*.stage_percentage' => [
                'bail', 'required', 'numeric', 'min:0', 'max:100',
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
                    'stage_percentage' => (float) $stage['stage_percentage'],
                ];
            }, $this->input('pipeline_stages'));

            return new CreatePipelineData([
                'space_id' => $this->input('space_id'),
                'pipeline_name' => $this->input('pipeline_name'),
                'pipeline_stages' => $pipelineStages,
            ]);
        });
    }
}
