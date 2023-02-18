<?php

namespace App\Domain\Authorization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    public function data(): array
    {
        $modules = collect(config('role.modules') ?? [])->map(
            fn ($privileges, $moduleName) => [
                'module' => $moduleName,
                'privileges' => array_keys($privileges),
            ]
        )->values()->toArray();

        $subModules = collect(config('role.submodules') ?? [])->map(
            fn ($module) => collect($module)->map(fn ($subModule) => array_keys($subModule))
        )->toArray();

        return [
            'privileges' => config('role.privileges') ?? [],
            'modules' => $modules,
            'submodules' => $subModules,
            'properties' => config('role.properties') ?? [],
        ];
    }
}
