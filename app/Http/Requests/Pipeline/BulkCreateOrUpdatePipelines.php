<?php

namespace App\Http\Requests\Pipeline;

use App\DTO\Pipeline\PutPipelineData;
use App\DTO\Pipeline\PutPipelineDataCollection;
use App\Models\Pipeline\Pipeline;
use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkCreateOrUpdatePipelines extends FormRequest
{
    protected ?PutPipelineDataCollection $putPipelineDataCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'default_pipeline_indexes' => [
                'bail', 'required', 'array', 'max:1',
            ],
            'default_pipeline_indexes.*' => [
                'integer',
            ],

            'pipelines' => [
                'bail', 'present', 'array',
            ],
            'pipelines.*.id' => [
                'bail', 'present', 'nullable', 'uuid',
                Rule::exists(Pipeline::class, 'id')->whereNull('deleted_at'),
            ],
            'pipelines.*.is_default' => [
                'bail', 'present', 'boolean',
            ],
            'pipelines.*.space_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Space::class, 'id')->whereNull('deleted_at'),
            ],
            'pipelines.*.pipeline_name' => [
                'bail', 'required', 'string', 'max:191',
            ],
            'pipelines.*.pipeline_stages' => [
                'bail', 'present', 'array',
            ],
            'pipelines.*.pipeline_stages.*.id' => [
                'bail', 'present', 'nullable', 'uuid',
                // TODO: validate existence of the stage entity
            ],
            'pipelines.*.pipeline_stages.*.stage_name' => [
                'bail', 'string', 'max:191',
            ],
            'pipelines.*.pipeline_stages.*.stage_percentage' => [
                'bail', 'required', 'numeric', 'min:0', 'max:100',
            ],
        ];
    }

    public function messages()
    {
        return [
            'default_pipeline_indexes.required' => 'The Default Pipeline must be selected.',
            'default_pipeline_indexes.min' => 'The Default Pipeline must be selected.',
            'default_pipeline_indexes.max' => 'Only one Default Pipeline can be selected.',
        ];
    }

    public function getPutPipelineDataCollection(): PutPipelineDataCollection
    {
        return $this->putPipelineDataCollection ??= value(function () {

            $collection = array_map(function (array $pipeline) {
                return new PutPipelineData([
                    'pipeline_id' => $pipeline['id'],
                    'space_id' => $pipeline['space_id'],
                    'pipeline_name' => $pipeline['pipeline_name'],
                    'pipeline_stages' => array_map(function (array $stage) {
                        static $order = 1;
                        return [
                            'stage_id' => $stage['id'],
                            'stage_name' => $stage['stage_name'],
                            'stage_order' => $order++,
                            'stage_percentage' => (float)$stage['stage_percentage'],
                        ];
                    }, $pipeline['pipeline_stages']),
                    'is_default' => (bool)$pipeline['is_default'],
                ]);
            }, $this->input('pipelines'));

            return new PutPipelineDataCollection($collection);

        });
    }

    protected function prepareForValidation()
    {
        $defaultPipelines = array_filter($this->input('pipelines.*.is_default'), function ($value) {

            return filter_var($value, FILTER_VALIDATE_BOOL);

        });

        $this->merge([
            'default_pipeline_indexes' => array_keys($defaultPipelines),
        ]);
    }
}
