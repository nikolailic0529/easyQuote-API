<?php namespace App\Http\Requests;

use App\Rules\{
    Recaptcha,
    UserNotDeactivated,
};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserSignInRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email'         => ['required', 'string', 'email', new UserNotDeactivated],
            'password'      => 'required|string',
            'remember_me'   => 'boolean',
            'local_ip'      => 'required|string|ip',
            'g_recaptcha'   => [Rule::requiredIf(setting('google_recaptcha_enabled') ?? false), 'string', new Recaptcha]
        ];
    }
}
