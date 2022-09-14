<?php

namespace App\Http\Requests\Role;

use App\Enum\CompanyType;
use App\Models\Company;
use App\Services\PermissionHelper;
use Illuminate\Support\Collection;
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
            'name' => ['required', 'string', 'min:2', 'max:60', Rule::unique('roles')->whereNull('deleted_at')],

            'privileges' => 'required|array',
            'privileges.*.module' => ['required', 'string', Rule::in(static::$modules)],
            'privileges.*.privilege' => ['required', 'string', $this->privilegeRule()],

            'privileges.*.submodules.*.submodule' => ['string', $this->subModuleRule()],
            'privileges.*.submodules.*.privilege' => ['string', $this->subModulePrivilegeRule()],

            'properties' => 'array',
            'properties.*' => 'required|array',
            'properties.*.key' => ['required', 'string', Rule::in(static::$properties)],
            'properties.*.value' => 'required|boolean',
        ];
    }
}
