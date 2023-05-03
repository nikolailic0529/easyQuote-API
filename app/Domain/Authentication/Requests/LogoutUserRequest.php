<?php

namespace App\Domain\Authentication\Requests;

use App\Domain\Authentication\Validation\Rules\PasswordMatches;
use App\Domain\Authentication\Validation\Rules\UserNotDeactivated;
use App\Domain\Recaptcha\Validation\Rules\Recaptcha;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogoutUserRequest extends FormRequest
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
            'email' => ['bail', 'required', 'string', 'email', new UserNotDeactivated(), Rule::exists(User::class)->where('already_logged_in', true)],
            'password' => ['required', 'string', new PasswordMatches()],
            'g_recaptcha' => ['required', 'string', new Recaptcha()],
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
