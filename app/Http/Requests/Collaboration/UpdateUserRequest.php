<?php namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'string|alpha',
            'middle_name' => 'nullable|string|alpha',
            'last_name' => 'string|alpha',
            'email' => [
                'string',
                'email',
                Rule::unique('users')->ignore($this->user)->whereNull('deleted_at')
            ],
            'phone' => 'nullable|string|min:4|phone',
            'timezone_id' => 'string|uuid|exists:timezones,id',
            'role_id' => [
                'string',
                'uuid',
                Rule::exists('roles', 'id')->whereNotNull('activated_at')
            ]
        ];
    }
}
