<?php namespace App\Http\Requests;

use App\Models\Data\Country;
use App\Models\Data\Timezone;
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
            'first_name'    => 'required|string|alpha',
            'middle_name'   => 'nullable|string|alpha',
            'last_name'     => 'required|string|alpha',
            'email'         => ['required', 'string', 'email', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password'      => 'required|string|min:6|confirmed',
            'phone'         => 'nullable|string|min:4|phone',
            'timezone_id'   => ['uuid', Rule::exists(Timezone::class, 'id')],
            'country_id'    => ['uuid', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
            'local_ip'      => 'required|string|ip'
        ];
    }
}
