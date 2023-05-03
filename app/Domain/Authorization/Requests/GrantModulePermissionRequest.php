<?php

namespace App\Domain\Authorization\Requests;

use App\Domain\Authorization\Facades\Permission;
use App\Domain\Authorization\Models\Role;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrantModulePermissionRequest extends FormRequest
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
            'roles.*' => ['required', 'uuid', 'distinct', Rule::exists(Role::class, 'id')->whereNull('deleted_at')],
            'module' => ['required', 'string', Rule::in(Permission::providedModules())],
            'level' => ['required', 'string', Rule::in(Permission::providedLevels())],
            'users' => ['present', 'array'],
            'users.*' => ['uuid', 'distinct', Rule::exists(User::class, 'id')->whereNull('deleted_at')],
        ];
    }
}
