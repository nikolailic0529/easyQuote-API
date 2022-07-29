<?php

namespace App\Http\Requests\Collaboration;

use App\DTO\Invitation\CreateInvitationData;
use App\DTO\SalesUnit\CreateSalesUnitRelationData;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteUserRequest extends FormRequest
{
    protected ?CreateInvitationData $createInvitationData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

            'email' => [
                'required', 'string', 'email',
                Rule::unique('users', 'email')->withoutTrashed(),
                Rule::unique('invitations', 'email')->withoutTrashed(),
            ],

            'host' => 'required|string|url',

            'role_id' => [
                'required', 'uuid',
                Rule::exists(Role::class, 'id')->withoutTrashed(),
            ],

            'team_id' => [
                'nullable', 'uuid',
                Rule::exists(Team::class, 'id')->withoutTrashed(),
            ],


            'sales_units' => ['bail', 'array'],
            'sales_units.*.id' => ['bail', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed()],

        ];
    }

    public function getCreateInvitationData(): CreateInvitationData
    {
        return $this->createInvitationData ??= new CreateInvitationData([
            'email' => $this->input('email'),
            'host' => $this->input('host'),
            'role_id' => $this->input('role_id'),
            'team_id' => $this->input('team_id'),
            'sales_units' => $this->collect('sales_units')
                ->map(static function (array $relation): CreateSalesUnitRelationData {
                    return new CreateSalesUnitRelationData(['id' => $relation['id']]);
                })
                ->all(),
        ]);
    }
}
