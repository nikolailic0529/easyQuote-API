<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\Request\PreparesNullValues;

class UpdateContactRequest extends FormRequest
{
    use PreparesNullValues;

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
            'first_name'    => 'required|string|filled|alpha',
            'last_name'     => 'required|string|filled|alpha',
            'phone'         => 'nullable|string|phone',
            'mobile'        => 'nullable|string',
            'email'         => 'nullable|string|email',
            'job_title'     => 'nullable|string',
            'picture'       => 'nullable|file|image|max:2048',
            'is_verified'   => 'nullable|boolean'
        ];
    }

    protected function prepareForValidation()
    {
        $this->prepareNullValues();

        $is_verified = (bool) $this->is_verified;

        $this->merge(compact('is_verified'));
    }

    protected function nullValues(): array
    {
        return ['phone', 'mobile', 'job_title', 'email', 'picture'];
    }
}
