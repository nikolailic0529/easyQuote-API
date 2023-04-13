<?php

namespace App\Domain\Authorization\Requests;

use App\Domain\Authorization\Contracts\ModuleRepository;
use App\Domain\Authorization\DataTransferObjects\CreateRoleData;
use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\Authorization\Enum\AccessEntityPipelineDirection;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Validation\Rules\ValidModulePrivilege;
use App\Domain\Authorization\Validation\Rules\ValidSubmoduleName;
use App\Domain\Authorization\Validation\Rules\ValidSubmodulePrivilege;
use App\Domain\Pipeline\Models\Pipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Optional;

final class CreateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'bail', 'required', 'string', 'max:60',
                Rule::unique(Role::class)->withoutTrashed(),
            ],

            'privileges' => ['required', 'array'],
            'privileges.*.module' => ['required', 'string', Rule::in(array_keys(config('role.modules')))],
            'privileges.*.privilege' => ['required', 'string', new ValidModulePrivilege(config('role.modules'))],

            'privileges.*.submodules.*.submodule' => [
                'string',
                new ValidSubmoduleName(config('role.submodules')),
            ],
            'privileges.*.submodules.*.privilege' => [
                'string',
                new ValidSubmodulePrivilege(config('role.submodules')),
            ],

            'properties' => ['array'],
            'properties.*' => ['required', 'array'],
            'properties.*.key' => ['required', 'string', Rule::in(config('role.properties'))],
            'properties.*.value' => ['required', 'boolean'],

            'access_data' => ['array'],
            'access_data.access_contact_direction' => [
                'bail',
                new Enum(AccessEntityDirection::class),
            ],
            'access_data.access_company_direction' => [
                'bail',
                new Enum(AccessEntityDirection::class),
            ],
            'access_data.access_opportunity_direction' => [
                'bail',
                new Enum(AccessEntityDirection::class),
            ],
            'access_data.access_opportunity_pipeline_direction' => [
                'bail',
                new Enum(AccessEntityPipelineDirection::class),
            ],
            'access_data.allowed_opportunity_pipelines' => [
                'bail', 'array',
            ],
            'access_data.allowed_opportunity_pipelines.*.pipeline_id' => [
                'bail',
                'required',
                'distinct',
                Rule::exists(Pipeline::class, (new Pipeline())->getKeyName())->withoutTrashed(),
            ],
            'access_data.access_worldwide_quote_direction' => [
                'bail',
                new Enum(AccessEntityDirection::class),
            ],
            'access_data.access_sales_order_direction' => [
                'bail',
                new Enum(AccessEntityDirection::class),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $privileges = $this->collect('privileges')->unique('module')->all();

        $this->merge(compact('privileges'));

        if (!$this->has('properties')) {
            return;
        }

        $properties = $this->collect('properties')
            ->transform(static function (mixed $value, string $key): array {
                return [
                    'key' => $key,
                    'value' => filter_var($value, FILTER_VALIDATE_BOOL),
                ];
            })
            ->all();

        $this->merge(['properties' => $properties]);
    }

    public function getCreateRoleData(): CreateRoleData
    {
        /** @var ModuleRepository $moduleRepository */
        $moduleRepository = $this->container->make(ModuleRepository::class);

        $permissions = $this->collect('privileges')
            ->map(static function (array $moduleData) use ($moduleRepository): array {
                $module = $moduleRepository->get($moduleData['module']);
                $permissionsOfModule = $module->getPrivilege($moduleData['privilege'])->permissions;

                $permissionsOfSubmodules = Collection::wrap($moduleData['submodules'] ?? [])
                    ->map(static function (array $submoduleData) use ($module): array {
                        $submoduleName = $submoduleData['submodule'];
                        $submodulePrivilege = $submoduleData['privilege'];

                        return $module->getSubmodule($submoduleName)
                            ->getPrivilege($submodulePrivilege)
                            ->permissions;
                    })
                    ->collapse()
                    ->all();

                return array_merge($permissionsOfModule, $permissionsOfSubmodules);
            })
            ->collapse();

        $properties = $this->collect('properties')
            ->lazy()
            ->whereStrict('value', true)
            ->pluck('key')
            ->collect();

        $permissions = $permissions->merge($properties)->all();

        return CreateRoleData::from([
            'name' => $this->input('name'),
            'permissions' => $permissions,
            'accessData' => $this->has('access_data') ? $this->input('access_data') : Optional::create(),
        ]);
    }
}
