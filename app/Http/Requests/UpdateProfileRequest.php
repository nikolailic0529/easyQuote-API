<?php

namespace App\Http\Requests;

use App\Models\Data\Country;
use App\Models\Data\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\Request\PreparesNullValues;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use PreparesNullValues;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name'                    => 'string|min:2|alpha_spaces',
            'middle_name'                   => 'nullable|string|alpha_spaces',
            'last_name'                     => 'string|min:2|alpha_spaces',
            'email'                         => ['string', 'email', Rule::unique('users')->ignore($this->user())->whereNull('deleted_at')],
            'phone'                         => 'nullable|string|min:4|phone',
            'timezone_id'                   => ['uuid', Rule::exists(Timezone::class, 'id')],
            'country_id'                    => ['uuid', Rule::exists(Country::class, 'id')->whereNull('deleted_at')],
            'picture'                       => 'image|max:2048',
            'delete_picture'                => 'nullable|boolean',
            'change_password'               => 'nullable|boolean',
            'current_password'              => [Rule::requiredIf(fn () => (bool) $this->change_password), 'string', 'password:api'],
            'password'                      => [Rule::requiredIf(fn () => (bool) $this->change_password), 'string', 'min:8', 'different:current_password', 'confirmed'],
            'default_route'                 => 'nullable|string|max:200',
            'recent_notifications_limit'    => 'integer|min:1|max:30'
        ];
    }

    public function messages()
    {
        return [
            'password.regex'            => 'The Password should contain uppercase and lowercase characters, digits, non-alphanumeric characters.',
            'current_password.password' => 'You have entered invalid current password.',
            'password.different'        => "Your new password shouldn't be same as your last password",
            'first_name.min'            => 'The first name/last name must be of at least :min characters.',
            'last_name.min'             => 'The first name/last name must be of at least :min characters.'
        ];
    }

    protected function nullValues()
    {
        return ['phone', 'middle_name'];
    }
}
