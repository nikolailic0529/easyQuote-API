<?php

namespace App\Http\Requests\Role;

use Illuminate\Validation\Rule;

class StoreRoleRequest extends Request
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
                'min:2',
                'max:60',
                Rule::unique('roles')->whereNull('deleted_at')
            ],
            'privileges' => 'required|array',
            'privileges.*.module' => [
                'required',
                'string',
                Rule::in(static::$modules)
            ],
            'privileges.*.privilege' => [
                'required',
                'string',
                $this->privilegeRule()
            ],
            'properties' => 'array',
            'properties.*' => 'required|array',
            'properties.*.key' => [
                'required',
                'string',
                Rule::in(static::$properties)
            ],
            'properties.*.value' => 'required|boolean'
        ];
    }
}
