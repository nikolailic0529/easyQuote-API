<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
            'first_name' => 'required|string|min:2|alpha',
            'last_name' => 'required|string|min:2|alpha',
            'phone' => 'nullable|string|min:4|phone',
            'mobile' => 'nullable|string|min:4',
            'email' => 'required|string|email',
            'job_title' => 'nullable|string|min:2',
            'picture' => 'file|image|max:2048',
            'is_verified' => 'boolean'
        ];
    }
}
