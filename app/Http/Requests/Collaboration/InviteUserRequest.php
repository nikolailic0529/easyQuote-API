<?php namespace App\Http\Requests\Collaboration;

use App\DTO\Invitation\CreateInvitationData;
use App\Models\Role;
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
                Rule::unique('users', 'email')->whereNull('deleted_at'),
                Rule::unique('invitations', 'email')->whereNull('deleted_at')
            ],

            'host' => 'required|string|url',

            'role_id' => [
                'required', 'uuid',
                Rule::exists(Role::class, 'id')->whereNull('deleted_at')
            ],

            'team_id' => [
                'nullable', 'uuid',
                Rule::exists(Team::class, 'id')->whereNull('deleted_at'),
            ]

        ];
    }

    public function getCreateInvitationData(): CreateInvitationData
    {
        return $this->createInvitationData ??= new CreateInvitationData([
           'email' => $this->input('email'),
           'host' => $this->input('host'),
           'role_id' => $this->input('role_id'),
           'team_id' => $this->input('team_id'),
        ]);
    }
}
