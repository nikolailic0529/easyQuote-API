<?php namespace App\Http\Requests\Collaboration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteUserRequest extends FormRequest
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
            'email' => 'required|string|email|unique:users',
            'role_id' => [
                'required',
                'string',
                'uuid',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->where('collaboration_id', request()->user()->id)
                        ->orWhere('is_system', true);
                })
            ]
        ];
    }
}
