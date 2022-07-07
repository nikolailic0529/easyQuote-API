<?php

namespace App\Http\Requests\Contact;

use App\DTO\Contact\CreateContactData;
use App\Enum\ContactType;
use App\Enum\GenderEnum;
use App\Models\Address;
use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
            'contact_type' => ['required', 'string', Rule::in(ContactType::getValues())],
            'gender' => ['nullable', 'string', new Enum(GenderEnum::class)],
            'first_name' => ['required', 'string', 'filled', 'max:100'],
            'last_name' => ['required', 'string', 'filled', 'max:100'],
            'phone' => ['nullable', 'string', 'phone', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'picture' => ['nullable', 'file', 'image', 'max:2048'],
            'is_verified' => ['nullable', 'boolean'],
            'addresses' => ['array'],
            'addresses.*' => ['string', 'uuid', Rule::exists(Address::class, 'id')->withoutTrashed()],
        ];
    }

    protected function prepareForValidation(): void
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

    public function getCreateContactData(): CreateContactData
    {
        return new CreateContactData([
            'contact_type' => $this->input('contact_type'),
            'gender' => $this->filled('gender')
                ? GenderEnum::from($this->input('gender'))
                : GenderEnum::Unknown,
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'phone' => $this->input('phone'),
            'mobile' => $this->input('mobile'),
            'email' => $this->input('email'),
            'job_title' => $this->input('job_title'),
            'picture' => $this->file('picture'),
            'is_verified' => $this->boolean('is_verified'),
            'addresses' => $this->input('addresses'),
        ]);
    }
}
