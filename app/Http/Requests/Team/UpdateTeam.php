<?php

namespace App\Http\Requests\Team;

use App\DTO\Team\UpdateTeamData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeam extends FormRequest
{
    protected ?UpdateTeamData $updateTeamData = null;

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

    public function getUpdateTeamData(): UpdateTeamData
    {
        return $this->updateTeamData ??= new UpdateTeamData([
            'team_name' => $this->input('team_name'),
            'monthly_goal_amount' => transform($this->input('monthly_goal_amount'), fn($value) => (float)$value)
        ]);
    }
}
