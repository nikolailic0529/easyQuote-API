<?php

namespace App\Http\Requests\OpportunityTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityTemplate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'form_data' => [
                'bail', 'present', 'array'
            ],
            'form_data.*' => [
                'array'
            ],
            'form_data.*.id' => [
                'uuid'
            ],
            'form_data.*.name' => [
                'string'
            ],
            'form_data.*.class' => [
                'string'
            ],
            'form_data.*.order' => [
                'integer'
            ],
            'form_data.*.child' => [
                'array'
            ],
            'form_data.*.child.*.id' => [
                'uuid'
            ],
            'form_data.*.child.*.class' => [
                'string'
            ],
            'form_data.*.child.*.position' => [
                'integer'
            ],
            'form_data.*.child.*.controls' => [
                'array'
            ],
            'form_data.*.child.*.controls.*.id' => [
                'string'
            ],
            'form_data.*.child.*.controls.*.name' => [
                'string'
            ],
            'form_data.*.child.*.controls.*.type' => [
                'string'
            ],
            'form_data.*.child.*.controls.*.class' => [
                'nullable', 'string'
            ],
            'form_data.*.child.*.controls.*.label' => [
                'nullable', 'string'
            ],
            'form_data.*.child.*.controls.*.value' => [
                'nullable', 'string'
            ],
            'form_data.*.child.*.controls.*.field_required' => [
                'boolean'
            ],
            'form_data.*.child.*.controls.*.possible_values' => [
                'string'
            ],
        ];
    }

    public function getTemplateSchema(): array
    {
        return $this->input('form_data');
    }
}
