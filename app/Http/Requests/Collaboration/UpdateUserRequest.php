<?php namespace App\Http\Requests\Collaboration;

use App\Models\Data\Timezone;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => ['string', 'alpha_spaces'],
            'middle_name' => ['nullable', 'string', 'alpha_spaces'],
            'last_name' => ['string', 'alpha_spaces'],
//            'email' => [
//                'string', 'email',
//                Rule::unique('users')->ignore($this->user)->whereNull('deleted_at')
//            ],
            'phone' => [
                'nullable', 'string', 'min:4', 'phone'
            ],
            'timezone_id' => [
                'bail', 'uuid',
                Rule::exists(Timezone::class, 'id')
            ],
            'role_id' => [
                'bail', 'uuid',
                Rule::exists('roles', 'id')->whereNotNull('activated_at')->whereNull('deleted_at')
            ],
            'team_id' => [
                'bail', 'uuid',
                Rule::exists(Team::class, 'id')->whereNull('deleted_at')
            ]
        ];
    }
}
