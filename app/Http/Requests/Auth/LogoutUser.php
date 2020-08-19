<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\PasswordMatches;
use App\Rules\Recaptcha;
use App\Rules\UserNotDeactivated;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogoutUser extends FormRequest
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
            'email'         => ['bail', 'required', 'string', 'email', new UserNotDeactivated, Rule::exists(User::class)->where('already_logged_in', true)],
            'password'      => ['required', 'string', new PasswordMatches],
            'g_recaptcha'   => ['required', 'string', new Recaptcha]
        ];
    }

    public function messages()
    {
        return [
            'email.exists' => 'Logoutable User must be already authenticated.',
        ];
    }

    public function getLogoutableUser(): User
    {
        return User::whereEmail($this->input('email'))->firstOrFail();
    }
}
