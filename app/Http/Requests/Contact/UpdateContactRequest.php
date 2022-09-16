<?php

namespace App\Http\Requests\Contact;

use App\DTO\Contact\UpdateContactData;
use App\DTO\MissingValue;
use App\Enum\ContactType;
use App\Enum\GenderEnum;
use App\Models\Address;
use App\Models\SalesUnit;
use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateContactRequest extends FormRequest
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
            'sales_unit_id' => ['bail', 'required', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed()],
            'address_id' => ['bail', 'nullable', 'uuid',
                Rule::exists(Address::class, (new Address())->getKeyName())->withoutTrashed()],
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

    public function getUpdateContactData(): UpdateContactData
    {
        $missing = new MissingValue();

        return new UpdateContactData([
            'sales_unit_id' => $this->input('sales_unit_id'),
            'address_id' => $this->whenFilled('address_id', value(...), static fn() => $missing),
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
        ]);
    }
}
