<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetActivitiesRequest extends FormRequest
{
    protected $types;

    protected $periods;

    public function __construct()
    {
        $this->types = __('activity.types');
        $this->periods = __('activity.periods');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'types' => [
                'nullable',
                'array'
            ],
            'types.*' => [
                'required',
                'string',
                Rule::in($this->types)
            ],
            'period' => [
                'nullable',
                'string',
                Rule::in($this->periods)
            ],
            'custom_period' => 'array',
            'custom_period.from' => [
                'required_with:custom_period',
                'string',
                'date_format:d/m/y'
            ],
            'custom_period.till' => [
                'required_with:custom_period',
                'string',
                'date_format:d/m/y'
            ],
            'causer_id' => [
                'nullable',
                'string',
                'uuid',
                'exists:users,id'
            ]
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->filled('type')) {
            return;
        }

        $type = mb_strtolower($this->type);
        $this->merge(compact('type'));
    }
}
