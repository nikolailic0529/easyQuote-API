<?php namespace App\Http\Requests\Role;

use Illuminate\Validation\Rule;
use Str;

class UpdateRoleRequest extends Request
{
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
                Rule::unique('roles')->whereNull('deleted_at')->ignore($this->role)
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
