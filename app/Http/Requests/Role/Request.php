<?php

namespace App\Http\Requests\Role;

use App\Services\PermissionHelper;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class Request extends FormRequest
{
    /**
     * Available Privileges.
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
     * Available Modules.
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

    public function validated()
    {
        $validated = parent::validated();

        $permissions = $this->collect('privileges')->map(
            function (array $module) {
                $modulePermissions = PermissionHelper::modulePermissions($module['module'] ?? null, $module['privilege'] ?? null);

                $subPermissions = Collection::wrap($module['submodules'] ?? [])
                    ->map(fn($submodule) => PermissionHelper::subModulePermissions(
                        $module['module'] ?? null,
                        $submodule['submodule'] ?? null,
                        $submodule['privilege'] ?? null
                    ))
                    ->collapse()
                    ->toArray();

                return array_merge($modulePermissions, $subPermissions);
            }
        )
            ->collapse();

        $properties = $this->collect('properties')->where('value', true)->pluck('key');

        $permissions = $permissions->merge($properties)->all();

        $permissionIDs = PermissionHelper::permissionKey($permissions);

        return array_merge($validated, [
            'permissions' => $permissionIDs,
        ]);
    }

    protected function prepareForValidation()
    {
        $privileges = collect($this->input('privileges'))->unique('module')->toArray();

        $this->merge(compact('privileges'));

        if (!$this->has('properties')) {
            return;
        }

        $properties = collect($this->input('properties'))
            ->transform(function ($value, $key) {
                $value = (bool)$value;
                return compact('key', 'value');
            })
            ->toArray();

        $this->merge(compact('properties'));
    }

    protected function privilegeRule(): Closure
    {
        return function ($attribute, $value, $fail) {
            $moduleAttrubute = Str::before($attribute, '.privilege').'.module';
            $module = $this->input($moduleAttrubute);

            $modulePrivileges = static::$privilegesMapping->get($module, []);
            $message = "Allowed Privileges for {$module} module: ".collect($modulePrivileges)->implodeWithWrap(' or ', "'");

            if (!isset(array_flip($modulePrivileges)[$value])) {
                $fail($message);
            }
        };
    }

    protected function subModuleRule(): Closure
    {
        return function ($attribute, $value, $fail) {
            $moduleName = (string)Str::of($attribute)->before('.submodules')->append('.module');

            $module = $this->input($moduleName);

            $subModules = array_keys(config('role.submodules')[$module] ?? []);

            $message = "Allowed Sub-modules for {$module} module: ".collect($subModules)->implodeWithWrap(' or ', "'");

            if (!in_array($value, $subModules)) {
                $fail($message);
            }
        };
    }

    protected function subModulePrivilegeRule(): Closure
    {
        return function ($attribute, $value, $fail) {
            $attribute = Str::of($attribute);

            $moduleName = (string)$attribute->before('.submodules')->append('.module');
            $subModuleName = (string)$attribute->before('.privilege')->append('.submodule');

            $module = $this->input($moduleName);
            $subModule = $this->input($subModuleName);

            $subModulePrivileges = (config('role.submodules')[$module] ?? [])[$subModule] ?? [];

            $message = "Allowed Privileges for {$subModule} sub-module: ".collect($subModulePrivileges)->keys()->implodeWithWrap(' or ', "'");

            if (filled($subModulePrivileges) && !isset($subModulePrivileges[$value])) {
                $fail($message);
            }
        };
    }
}
