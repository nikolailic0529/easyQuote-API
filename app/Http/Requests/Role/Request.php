<?php

namespace App\Http\Requests\Role;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Str;

abstract class Request extends FormRequest
{
    /**
     * Available Privileges
     *
     * @var array
     */
    protected static $privileges;

    /**
     * Privileges Mapping with specified Modules.
     *
     * @var \Illuminate\Support\Collection
     */
    protected static $privilegesMapping;

    /**
     * Available Modules
     *
     * @var array
     */
    protected static $modules;

    /**
     * Role Properties keys.
     *
     * @var array
     */
    protected static $properties;

    public function __construct()
    {
        static::$privileges = collect(config('role.privileges'))->toArray();
        static::$privilegesMapping = collect(config('role.modules'))->eachKeys();
        static::$modules = static::$privilegesMapping->keys()->toArray();
        static::$properties = config('role.properties');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();

    protected function prepareForValidation()
    {
        if (!$this->has('properties')) {
            return;
        }

        $properties = collect($this->input('properties'))
            ->transform(function ($value, $key) {
                $value = (bool) $value;
                return compact('key', 'value');
            })
            ->toArray();

        $this->merge(compact('properties'));
    }

    protected function privilegeRule(): Closure
    {
        return function ($attribute, $value, $fail) {
            $moduleAttrubute = Str::before($attribute, '.privilege') . '.module';
            $module = $this->input($moduleAttrubute);

            $modulePrivileges = static::$privilegesMapping->get($module, []);
            $message = "The privilege for `{$module}` module must be " . collect($modulePrivileges)->implodeWithWrap(' or ', '`');

            if (!isset(array_flip($modulePrivileges)[$value])) {
                $fail($message);
            }
        };
    }
}
