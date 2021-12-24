<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class UpdateGroupDescriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                Rule::notIn($this->groupDescription()->where('id', '!==', $this->group)->pluck('name'))
            ],
            'search_text' => 'required|string|min:1',
            'rows' => 'required|array',
            'rows.*' => 'required|string|uuid|exists:imported_rows,id'
        ];
    }

    public function messages()
    {
        return [
            'name.not_in' => 'The selected Group name is already taken.'
        ];
    }

    public function groupDescription(): Collection
    {
        return collect(($this->route('quote')->activeVersion ?? $this->route('quote'))->group_description);
    }

    public function group()
    {
        return $this->groupDescription()->firstWhere('id', $this->group);
    }

    public function groupName()
    {
        return data_get($this->group(), 'name');
    }

    protected function passedValidation()
    {
        request()->merge([
            'group_name' => $this->groupName()
        ]);
    }
}
