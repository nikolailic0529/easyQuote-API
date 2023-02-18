<?php

namespace App\Domain\Company\Requests;

use App\Domain\Company\DataTransferObjects\UpdateCompanyContactData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyContactRequest extends FormRequest
{
    protected ?UpdateCompanyContactData $contactData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required|string|filled',
            'last_name' => 'required|string|filled',
            'phone' => 'present|nullable|string',
            'mobile' => 'present|nullable|string',
            'email' => 'present|nullable|string|email',
            'job_title' => 'present|nullable|string',
//            'picture' => 'nullable|file|image|max:2048',
            'is_verified' => 'present|boolean',
        ];
    }

    public function getUpdateContactData(): UpdateCompanyContactData
    {
        return $this->contactData ??= new \App\Domain\Company\DataTransferObjects\UpdateCompanyContactData([
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'phone' => $this->input('phone'),
            'mobile' => $this->input('mobile'),
            'email' => $this->input('email'),
            'job_title' => $this->input('job_title'),
            'is_verified' => $this->boolean('is_verified'),
        ]);
    }
}
