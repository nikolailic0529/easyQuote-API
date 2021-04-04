<?php

namespace App\Http\Requests\Team;

use App\DTO\Team\CreateTeamData;
use App\Models\BusinessDivision;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTeam extends FormRequest
{
    protected ?CreateTeamData $createTeamData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'team_name' => [
                'bail', 'required', 'string', 'max:191'
            ],
            'business_division_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(BusinessDivision::class, 'id')
            ],
            'monthly_goal_amount' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999999'
            ],
            'team_leaders' => [
                'bail', 'array'
            ],
            'team_leaders.*' => [
                'bail', 'uuid',
                Rule::exists(User::class, 'id')->whereNull('deleted_at')
            ]
        ];
    }

    public function getCreateTeamData(): CreateTeamData
    {
        return $this->createTeamData ??= new CreateTeamData([
            'team_name' => $this->input('team_name'),
            'business_division_id' => $this->input('business_division_id'),
            'monthly_goal_amount' => transform($this->input('monthly_goal_amount'), fn($value) => (float)$value),
            'team_leader_user_ids' => $this->input('team_leaders') ?? []
        ]);
    }
}
