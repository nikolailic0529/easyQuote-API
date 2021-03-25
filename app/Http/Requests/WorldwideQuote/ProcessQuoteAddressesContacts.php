<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\QuoteStages\AddressesContactsStage;
use App\DTO\WorldwideQuote\OpportunityAddressData;
use App\DTO\WorldwideQuote\OpportunityAddressDataCollection;
use App\DTO\WorldwideQuote\OpportunityContactData;
use App\DTO\WorldwideQuote\OpportunityContactDataCollection;
use App\Enum\PackQuoteStage;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Template\QuoteTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ProcessQuoteAddressesContacts extends FormRequest
{
    protected ?AddressesContactsStage $stage = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')->where('type', 'Internal'),
            ],
            'quote_currency_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'quote_template_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(QuoteTemplate::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')
            ],
            'quote_expiry_date' => [
                'bail', 'required', 'date_format:Y-m-d'
            ],
            'buy_price' => [
                'bail', 'required', 'numeric', 'min:0', 'max:999999999',
            ],

            'addresses' => [
                'bail', 'required', 'array'
            ],
            'addresses.*.id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Address::class, 'id')->whereNull('deleted_at')
            ],
            'addresses.*.address_type' => [
                'bail', 'required', 'string', 'in:Invoice,Machine,Client,Software'
            ],
            'addresses.*.address_1' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'addresses.*.address_2' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'addresses.*.city' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'addresses.*.state' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'addresses.*.post_code' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'addresses.*.country_id' => [
                'bail', 'required', 'uuid', Rule::exists(Country::class, 'id')->whereNull('deleted_at')
            ],
            'addresses.*.is_default' => [
                'bail', 'nullable', 'boolean'
            ],

            'contacts' => [
                'bail', 'required', 'array'
            ],
            'contacts.*.id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Contact::class, 'id')->whereNull('deleted_at')
            ],
            'contacts.*.contact_type' => [
                'bail', 'required', 'string', 'in:Hardware,Software'
            ],
            'contacts.*.first_name' => [
                'bail', 'required', 'string', 'filled', 'max:191'
            ],
            'contacts.*.last_name' => [
                'bail', 'required', 'string', 'filled', 'max:191'
            ],
            'contacts.*.email' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'contacts.*.mobile' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'contacts.*.phone' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'contacts.*.job_title' => [
                'bail', 'nullable', 'string', 'max:191'
            ],
            'contacts.*.is_verified' => [
                'bail', 'nullable', 'boolean'
            ],
            'contacts.*.is_default' => [
                'bail', 'nullable', 'boolean'
            ],



            'payment_terms' => [
                'bail', 'required', 'string', 'max:500'
            ],

            'stage' => [
                'bail', 'required', Rule::in(PackQuoteStage::getLabels())
            ],

        ];
    }

    public function getStage(): AddressesContactsStage
    {
        return $this->stage ??= with(true, function () {

            $addressCollection = with($this->input('addresses'), function (array $addresses) {
                $collection = array_map(fn(array $address) => new OpportunityAddressData([
                    'address_id' => $address['id'] ?? null,
                    'address_1' => $address['address_1'] ?? null,
                    'address_type' => $address['address_type'],
                    'country_id' => $address['country_id'],
                    'address_2' => $address['address_2'] ?? null,
                    'city' => $address['city'] ?? null,
                    'state' => $address['state'] ?? null,
                    'post_code' => $address['post_code'] ?? null,
                    'is_default' => (bool)($address['is_default'] ?? false),
                ]), $addresses);

                return new OpportunityAddressDataCollection($collection);
            });

            $contactsCollection = with($this->input('contacts'), function (array $contacts) {
                $collection = array_map(fn(array $contact) => new OpportunityContactData([
                    'contact_id' => $contact['id'] ?? null,
                    'contact_type' => $contact['contact_type'],
                    'first_name' => $contact['first_name'],
                    'last_name' => $contact['last_name'],
                    'email' => $contact['email'] ?? null,
                    'mobile' => $contact['mobile'] ?? null,
                    'phone' => $contact['phone'] ?? null,
                    'job_title' => $contact['job_title'] ?? null,
                    'is_verified' => (bool)($contact['is_verified'] ?? false),
                    'is_default' => (bool)($contact['is_default'] ?? false),
                ]), $contacts);

                return new OpportunityContactDataCollection($collection);
            });

            return new AddressesContactsStage([
                'company_id' => $this->input('company_id'),
                'quote_currency_id' => $this->input('quote_currency_id'),
                'quote_template_id' => $this->input('quote_template_id'),
                'quote_expiry_date' => Carbon::createFromFormat('Y-m-d', $this->input('quote_expiry_date')),
                'buy_price' => (float)$this->input('buy_price'),
                'addresses' => $addressCollection,
                'contacts' => $contactsCollection,
                'payment_terms' => $this->input('payment_terms'),
                'stage' => PackQuoteStage::getValueOfLabel($this->input('stage')),
            ]);

        });
    }
}
