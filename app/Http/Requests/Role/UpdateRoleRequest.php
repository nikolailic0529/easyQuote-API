<?php namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Str;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Available Privileges
     *
     * @var array
     */
    protected $privileges;

    /**
     * Privileges Mapping with specified Modules.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $privilegesMapping;

    /**
     * Available Modules
     *
     * @var array
     */
    protected $modules;

    public function __construct()
    {
        $this->privileges = collect(config('role.privileges'))->toArray();
        $this->privilegesMapping = collect(config('role.modules'))->eachKeys();
        $this->modules = $this->privilegesMapping->keys()->toArray();
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
                Rule::in($this->modules)
            ],
            'privileges.*.privilege' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $moduleAttrubute = Str::before($attribute, '.privilege') . '.module';
                    $module = $this->input($moduleAttrubute);

                    $modulePrivileges = $this->privilegesMapping->get($module, []);
                    $message = "The privilege for `{$module}` module must be " . collect($modulePrivileges)->implodeWithWrap(' or ', '`');

                    if (!isset(array_flip($modulePrivileges)[$value])) {
                        $fail($message);
                    }
                }
            ]
        ];
    }
}
