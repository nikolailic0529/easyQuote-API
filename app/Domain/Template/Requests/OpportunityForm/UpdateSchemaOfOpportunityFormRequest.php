<?php

namespace App\Domain\Template\Requests\OpportunityForm;

use App\Domain\Template\DataTransferObjects\UpdateOpportunityFormSchemaData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSchemaOfOpportunityFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'form_data' => [
                'bail', 'present', 'array',
            ],
            ...$this->buildSchemaValidationRules('form_data'),
            'sidebar_0' => [
                'bail', 'present', 'array',
            ],
            ...$this->buildSchemaValidationRules('sidebar_0'),
        ];
    }

    public function getFormSchema(): array
    {
        return $this->input('form_data');
    }

    public function getUpdateSchemaData(): UpdateOpportunityFormSchemaData
    {
        return UpdateOpportunityFormSchemaData::from($this);
    }

    protected function buildSchemaValidationRules(string $name): array
    {
        return [
            "$name.*" => [
                'array',
            ],
            "$name.*.id" => [
                'uuid',
            ],
            "$name.*.name" => [
                'string',
            ],
            "$name.*.class" => [
                'string',
            ],
            "$name.*.order" => [
                'integer',
            ],
            "$name.*.child" => [
                'array',
            ],
            "$name.*.child.*.id" => [
                'uuid',
            ],
            "$name.*.child.*.class" => [
                'string',
            ],
            "$name.*.child.*.position" => [
                'integer',
            ],
            "$name.*.child.*.controls" => [
                'array',
            ],
            "$name.*.child.*.controls.*.id" => [
                'string',
            ],
            "$name.*.child.*.controls.*.name" => [
                'string',
            ],
            "$name.*.child.*.controls.*.type" => [
                'string',
            ],
            "$name.*.child.*.controls.*.class" => [
                'nullable', 'string',
            ],
            "$name.*.child.*.controls.*.label" => [
                'nullable', 'string',
            ],
            "$name.*.child.*.controls.*.value" => [
                'nullable', 'string',
            ],
            "$name.*.child.*.controls.*.field_required" => [
                'boolean',
            ],
            "$name.*.child.*.controls.*.possible_values" => [
                'string',
            ],
        ];
    }
}
