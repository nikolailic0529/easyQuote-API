<?php

namespace App\Domain\User\Requests;

use App\Domain\Authorization\Facades\Permission;
use App\Domain\Authorization\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListByRolesRequest extends FormRequest
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
            'granted_module' => ['required', 'string', Rule::in(Permission::providedModules())],
        ];
    }
}
