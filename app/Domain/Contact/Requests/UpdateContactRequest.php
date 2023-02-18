<?php

namespace App\Domain\Contact\Requests;

use App\Domain\Address\Models\Address;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Enum\ContactType;
use App\Domain\Contact\Enum\GenderEnum;
use App\Domain\SalesUnit\Models\SalesUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateContactRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sales_unit_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed(),
            ],
            'address_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Address::class, (new Address())->getKeyName())->withoutTrashed(),
            ],
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
            'company_relations' => ['bail', 'nullable', 'array'],
            'company_relations.*.id' => [
                'bail', 'uuid', 'distinct', Rule::exists(Company::class, 'id')->withoutTrashed(),
            ],
            'company_relations.*.is_default' => [
                'bail', 'boolean',
            ],
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

    public function getUpdateContactData(): \App\Domain\Contact\DataTransferObjects\UpdateContactData
    {
        $payload = $this->all();

        if ($this->isNotFilled('address_id')) {
            unset($payload['address_id']);
            $this->offsetUnset('address_id');
        }

        return \App\Domain\Contact\DataTransferObjects\UpdateContactData::from($this);
    }

    protected function prepareNullValues(): void
    {
        if (!method_exists($this, 'nullValues')) {
            return;
        }

        $nullValues = array_map(fn ($value) => ($value === 'null') ? null : $value, $this->only($this->nullValues()));

        $this->merge($nullValues);
    }
}
