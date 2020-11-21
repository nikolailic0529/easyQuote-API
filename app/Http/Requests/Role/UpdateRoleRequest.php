<?php namespace App\Http\Requests\Role;

use App\Models\Company;
use Illuminate\Validation\Rule;

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
            'name' => ['required', 'string', 'min:2', 'max:60', Rule::unique('roles')->whereNull('deleted_at')->ignore($this->role)],

            'privileges' => 'required|array',
            'privileges.*.module' => ['required', 'string', Rule::in(static::$modules)],
            'privileges.*.privilege' => ['required', 'string', $this->privilegeRule()],

            'privileges.*.submodules.*.submodule' => ['string', $this->subModuleRule()],
            'privileges.*.submodules.*.privilege' => ['string', $this->subModulePrivilegeRule()],

            'properties' => 'array',
            'properties.*' => 'required|array',
            'properties.*.key' => ['required', 'string', Rule::in(static::$properties)],
            'properties.*.value' => 'required|boolean',
            'companies.*' => ['uuid', Rule::exists(Company::class, 'id')->where('type', Company::INT_TYPE)->whereNull('deleted_at')]
        ];
    }
}
