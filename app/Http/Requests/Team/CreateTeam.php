<?php

namespace App\Http\Requests\Team;

use App\DTO\Team\CreateTeamData;
use Illuminate\Foundation\Http\FormRequest;

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
            'monthly_goal_amount' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999999'
            ]
        ];
    }

    public function getCreateTeamData(): CreateTeamData
    {
        return $this->createTeamData ??= new CreateTeamData([
            'team_name' => $this->input('team_name'),
            'monthly_goal_amount' => transform($this->input('monthly_goal_amount'), fn($value) => (float)$value)
        ]);
    }
}
