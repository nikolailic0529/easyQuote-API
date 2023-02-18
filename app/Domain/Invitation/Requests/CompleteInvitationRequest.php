<?php

namespace App\Domain\Invitation\Requests;

use App\Domain\Invitation\DataTransferObjects\RegisterUserData;
use Illuminate\Foundation\Http\FormRequest;

class CompleteInvitationRequest extends FormRequest
{
    protected ?RegisterUserData $registerUserData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|string|alpha_spaces',
            'middle_name' => 'nullable|string|alpha_spaces',
            'last_name' => 'required|string|alpha_spaces',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|min:4|phone',
            'timezone_id' => 'required|string|size:36|exists:timezones,id',
        ];
    }

    public function getRegisterUserData(): RegisterUserData
    {
        return $this->registerUserData ??= new RegisterUserData([
            'first_name' => $this->input('first_name'),
            'middle_name' => $this->input('middle_name'),
            'last_name' => $this->input('last_name'),
            'password' => $this->input('password'),
            'phone' => $this->input('phone'),
            'timezone_id' => $this->input('timezone_id'),
        ]);
    }
}
