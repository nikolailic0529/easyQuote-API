<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\Request\PreparesNullValues;

class UpdateContactRequest extends FormRequest
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
            'contact_type' => 'required|string|in:Hardware,Software,Invoice',
            'first_name' => 'required|string|filled',
            'last_name' => 'required|string|filled',
            'phone' => 'nullable|string|phone',
            'mobile' => 'nullable|string',
            'email' => 'nullable|string|email',
            'job_title' => 'nullable|string',
            'picture' => 'nullable|file|image|max:2048',
            'is_verified' => 'nullable|boolean'
        ];
    }

    protected function prepareForValidation()
    {
        $this->prepareNullValues();

        $this->merge([
            'is_verified' => $this->boolean('is_verified'),
        ]);
    }

    protected function nullValues(): array
    {
        return ['phone', 'mobile', 'job_title', 'email', 'picture'];
    }
}
