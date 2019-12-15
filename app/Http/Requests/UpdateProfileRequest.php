<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UpdateProfileRequest extends FormRequest
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
            'first_name' => 'string|min:2',
            'middle_name' => 'nullable|string',
            'last_name' => 'string|min:2',
            'email' => [
                'string',
                'email',
                Rule::unique('users')->ignore($this->user())->whereNull('deleted_at')
            ],
            'phone' => 'nullable|string|min:4',
            'timezone_id' => 'string|uuid|exists:timezones,id',
            'picture' => 'image|max:2048',
            'delete_picture' => 'nullable|boolean',
            'change_password' => 'nullable|boolean',
            'current_password' => [
                Rule::requiredIf(function () {
                    return (bool) $this->change_password;
                }),
                'string',
                'password:api'
            ],
            'password' => [
                Rule::requiredIf(function () {
                    return (bool) $this->change_password;
                }),
                'string',
                'min:8',
                'different:current_password',
                'confirmed'
            ]
        ];
    }

    public function messages()
    {
        return [
            'password.regex' => 'The Password should contain uppercase and lowercase characters, digits, non-alphanumeric characters.',
            'current_password.password' => 'You have entered not valid password.',
            'password.different' => 'A new password must be different.'
        ];
    }
}
