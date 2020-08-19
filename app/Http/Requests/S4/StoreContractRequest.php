<?php

namespace App\Http\Requests\S4;

use App\Contracts\Repositories\AddressRepositoryInterface as Addresses;
use App\Models\Customer\Customer;
use App\Services\ContactService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\{
    Arr,
    Str,
    Carbon,
    Collection,
};
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    protected Addresses $addressRepository;

    protected ContactService $contactService;

    public function __construct(Addresses $addressRepository, ContactService $contactService)
    {
        $this->addressRepository = $addressRepository;
        $this->contactService = $contactService;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_name'                     => 'required|string|min:2',
            'rfq_number'                        => ['bail', 'required', 'string', 'min:2', 'regex:/^[[:alnum:]]+$/i', 'max:20', Rule::unique('customers', 'rfq')->whereNull('deleted_at')],
            'service_levels'                    => 'nullable|array',
            'service_levels.*.service_level'    => 'bail|required|string|min:2',
            'quotation_valid_until'             => 'bail|required|string|date_format:m/d/Y',
            'support_start_date'                => 'bail|required|string|date_format:Y-m-d',
            'support_end_date'                  => 'bail|required|string|date_format:Y-m-d',
            'invoicing_terms'                   => 'bail|required|string|min:2|max:2500',
            'country'                           => ['bail', 'required', 'string', 'size:2', Rule::exists('countries', 'iso_3166_2')->whereNull('deleted_at')],
            'addresses'                         => 'array',
            'addresses.*'                       => 'bail|required|array',
            'addresses.*.address_type'          => 'bail|required|string|in:Equipment,Software',
            'addresses.*.address_1'             => 'bail|required|string|min:2',

            /** Non-mandatory */
            'addresses.*.address_2'             => 'nullable|string|min:2',
            'addresses.*.city'                  => 'nullable|string|min:2',
            'addresses.*.state'                 => 'nullable|string|min:2',
            'addresses.*.post_code'             => 'nullable|string|min:4',
            'addresses.*.country_code'          => 'nullable|string|size:2|exists:countries,iso_3166_2',
            'addresses.*.contact_name'          => 'nullable|string|min:2',
            'addresses.*.contact_number'        => 'nullable|string|min:4',
            'addresses.*.contact_email'         => 'nullable|string|email',
        ];
    }

    public function messages()
    {
        return [
            'rfq_number.unique' => 'A Quote already exists for the provided RFQ.'
        ];
    }

    protected function prepareForValidation()
    {
        customlog(['message' => S4_CS_02], $this->toArray());

        $rfq_number = strtoupper($this->rfq_number);

        $this->merge(compact('rfq_number'));
    }

    protected function failedValidation(Validator $validator)
    {
        slack()
            ->title('Receiving RFQ / Data from S4')
            ->status([S4_CSF_01, 'Proposed RFQ' => $this->rfq_number, 'Reason' => optional($validator->errors())->first()])
            ->image(assetExternal(SN_IMG_S4RDF))
            ->queue();

        parent::{__FUNCTION__}($validator);
    }

    public function validated()
    {
        $validated = collect(parent::validated());

        $countryId = app('country.repository')->findIdByCode($this->country);

        $addresses = $this->getAddresses();

        $contacts = $this->contactService->retrieveContactsFromAddresses($addresses);

        $dates = $this->formatDates();

        $validated = $validated->merge([
            'country_id' => $countryId,
            'addresses' => $addresses->modelKeys(),
            'contacts' => $contacts->modelKeys(),
            'source' => Customer::S4_SOURCE
        ])->merge($dates);

        return $validated->toArray();
    }

    protected function getAddresses(): EloquentCollection
    {
        $addresses = $this->addresses;
        $codes = data_get($addresses, '*.country_code');

        $countries = Collection::wrap(app('country.repository')->findIdByCode($codes));

        $addresses = collect($addresses)->transform(
            fn ($address) => Arr::set($address, 'country_id', $countries->get(optional($address)['country_code']))
        )
            ->toArray();

        return $this->addressRepository->findOrCreateMany($addresses);
    }

    protected function formatDates(): array
    {
        $dates = ['support_start_date', 'support_end_date', 'quotation_valid_until'];

        $formats = collect(Arr::only($this->rules(), $dates))
            ->map(
                fn ($rule) => Str::of($rule)->explode('|')->first(fn ($rule) => Str::contains($rule, 'date_format'))
            )->map(
                fn ($rule) => Str::after($rule, 'date_format:')
            );

        return collect($this->only($dates))
            ->transform(fn ($date, $key) => Carbon::createFromFormat($formats->get($key), $date))
            ->toArray();
    }
}
