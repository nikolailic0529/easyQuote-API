<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserSignUpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|string|alpha',
            'middle_name' => 'required|string|alpha',
            'last_name' => 'required|string|alpha',
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|min:4|phone',
            'timezone_id' => 'required|string|size:36|exists:timezones,id',
            'local_ip' => 'required|string|ip'
        ];
    }
}
