<?php

namespace App\Http\Requests\Collaboration;

use App\DTO\SalesUnit\CreateSalesUnitRelationData;
use App\DTO\User\UpdateUserData;
use App\Models\Data\Timezone;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    protected ?UpdateUserData $updateUserData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'first_name' => ['string', 'alpha_spaces'],
            'middle_name' => ['nullable', 'string', 'alpha_spaces'],
            'last_name' => ['string', 'alpha_spaces'],
            'phone' => [
                'nullable', 'string', 'min:4', 'phone',
            ],
            'timezone_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Timezone::class, 'id'),
            ],
            'sales_units' => ['bail', 'required', 'array'],
            'sales_units.*.id' => ['bail', 'required', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed()],
            'role_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Role::class, 'id')->whereNotNull('activated_at')->withoutTrashed(),
            ],
            'team_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Team::class, 'id')->withoutTrashed(),
            ],
        ];
    }

    public function getUpdateUserData(): UpdateUserData
    {
        return $this->updateUserData ??= new UpdateUserData([
            'first_name' => $this->input('first_name'),
            'middle_name' => $this->input('middle_name'),
            'last_name' => $this->input('last_name'),
            'phone' => $this->input('phone'),
            'timezone_id' => $this->input('timezone_id'),
            'sales_units' => $this->collect('sales_units')
                ->map(static function (array $relation): CreateSalesUnitRelationData {
                    return new CreateSalesUnitRelationData(['id' => $relation['id']]);
                })
                ->all(),
            'role_id' => $this->input('role_id'),
            'team_id' => $this->input('team_id'),
        ]);
    }
}
