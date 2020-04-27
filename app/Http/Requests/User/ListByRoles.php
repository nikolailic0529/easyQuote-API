<?php

namespace App\Http\Requests\User;

use App\Facades\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListByRoles extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'uuid', Rule::exists(Role::class, 'id')->whereNull('deleted_at')],
            'granted_module' => ['required', 'string', Rule::in(Permission::providedModules())]
        ];
    }
}
