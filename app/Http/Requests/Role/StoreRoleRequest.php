<?php namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
            'name' => 'required|string|min:2|max:60',
            'privileges' => 'required|array',
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
