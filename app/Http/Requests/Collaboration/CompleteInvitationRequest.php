<?php namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;

class CompleteInvitationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|min:4',
            'timezone_id' => 'required|string|size:36|exists:timezones,id'
        ];
    }
}
