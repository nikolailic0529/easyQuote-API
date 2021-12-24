<?php

namespace App\Http\Requests\Contact;

use App\DTO\Contact\CreateContactData;
use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    use PreparesNullValues;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
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
            'is_verified' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->prepareNullValues();

        $is_verified = (bool)$this->is_verified;

        $this->merge(compact('is_verified'));
    }

    protected function nullValues(): array
    {
        return ['phone', 'mobile', 'job_title', 'email', 'picture'];
    }

    public function getCreateContactData(): CreateContactData
    {
        return new CreateContactData([
            'contact_type' => $this->input('contact_type'),
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'phone' => $this->input('phone'),
            'mobile' => $this->input('mobile'),
            'email' => $this->input('email'),
            'job_title' => $this->input('job_title'),
            'picture' => $this->file('picture'),
            'is_verified' => $this->boolean('is_verified'),
        ]);
    }
}
