<?php namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Available Privileges
     *
     * @var array
     */
    protected $privileges;

    /**
     * Available Modules
     *
     * @var array
     */
    protected $modules;

    public function __construct()
    {
        $this->privileges = collect(config('role.privileges'))->toArray();
        $this->modules = collect(config('role.modules'))->keys()->toArray();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string|min:2|max:60',
            'privileges' => 'array',
            'privileges.*.module' => [
                'required',
                'string',
                Rule::in($this->modules)
            ],
            'privileges.*.privilege' => [
                'required',
                'string',
                Rule::in($this->privileges)
            ]
        ];
    }
}
