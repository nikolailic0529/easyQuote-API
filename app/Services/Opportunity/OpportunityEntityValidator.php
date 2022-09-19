<?php

namespace App\Services\Opportunity;

use App\Enum\AddressType;
use App\Enum\ContactType;
use App\Enum\VAT;
use App\Models\Opportunity;
use App\Rules\Count;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Factory;

class OpportunityEntityValidator
{
    public function __construct(protected Factory $validatorFactory)
    {
    }

    public function __invoke(Opportunity $opportunity): MessageBag
    {
        return tap(new MessageBag(), function (MessageBag $errors) use ($opportunity): void {

            $opportunity->loadMissing([
                'primaryAccount.addresses',
                'primaryAccount.contacts',
                'primaryAccount.vendors',

                'primaryAccountContact',

                'endUser.addresses',
                'endUser.contacts',
                'endUser.vendors',

                'opportunitySuppliers',
            ]);

            $opportunity->primaryAccount?->addresses?->makeVisible('pivot');
            $opportunity->endUser?->addresses?->makeVisible('pivot');

            $opportunity->primaryAccount?->contacts?->makeVisible('pivot');
            $opportunity->endUser?->contacts?->makeVisible('pivot');

            $opportunity->is_contract_duration_checked = (bool) $opportunity->is_contract_duration_checked;

            $validator = $this->validatorFactory->make(data: $opportunity->toArray(), rules: $this->getRules(),
                messages: $this->getMessages());

            $validator->passes();

            $errors->merge($validator->getMessageBag());
        });
    }

    protected function getRules(): array
    {
        return [
            'primary_account' => ['bail', 'required'],
            'primary_account.email' => ['exclude_without:primary_account', 'bail', 'nullable', 'email:filter'],
            'primary_account.vat_type' => ['exclude_without:primary_account', 'bail', 'required'],
            'primary_account.vat' => [
                'exclude_without:primary_account', 'bail', 'required_if:primary_account.vat_type,'.VAT::VAT_NUMBER,
            ],
            'primary_account.vendors' => ['exclude_without:primary_account', 'bail', 'required'],
            'primary_account.addresses' => [
                'exclude_without:primary_account', 'bail', 'required',
                (new Count())
                    ->where('address_type', AddressType::INVOICE)
                    ->where('pivot.is_default', true)
                    ->exactly(1)
                    ->setExactMessage("Primary Account must have exactly :limit default invoice address."),
                (new Count())
                    ->whereIn('address_type', [AddressType::HARDWARE, AddressType::SOFTWARE])
                    ->min(1)
                    ->setMinMessage("Primary Account must have at least :limit software/hardware address."),
            ],
            'primary_account.contacts' => [
                'exclude_without:primary_account', 'bail', 'required',
                (new Count())
                    ->whereIn('contact_type', [ContactType::HARDWARE, ContactType::SOFTWARE])
                    ->min(1)
                    ->setMinMessage("Primary Account must have at least :limit software/hardware contact."),
            ],

            'primary_account_contact' => ['bail', 'required'],
            'primary_account_contact.email' => [
                'exclude_without:primary_account_contact', 'bail', 'required', 'email:filter',
            ],

            'end_user' => ['bail', 'required'],
            'end_user.email' => ['exclude_without:end_user', 'bail', 'nullable', 'email:filter'],
            'end_user.vat_type' => ['exclude_without:end_user', 'bail', 'required'],
            'end_user.vat' => ['exclude_without:end_user', 'bail', 'required_if:end_user.vat_type,'.VAT::VAT_NUMBER],
            'end_user.addresses' => [
                'exclude_without:end_user', 'bail', 'required',
                (new Count())
                    ->where('address_type', AddressType::INVOICE)
                    ->where('pivot.is_default', true)
                    ->exactly(1)
                    ->setExactMessage("End Customer must have exactly :limit default invoice address."),
                (new Count())
                    ->whereIn('address_type', [AddressType::HARDWARE, AddressType::SOFTWARE])
                    ->min(1)
                    ->setExactMessage("End Customer must have at least :limit software/hardware address."),
            ],
            'end_user.contacts' => [
                'exclude_without:end_user', 'bail', 'required',
                (new Count())
                    ->whereIn('contact_type', [ContactType::HARDWARE, ContactType::SOFTWARE])
                    ->min(1)
                    ->setMinMessage("End Customer must have at least :limit software/hardware contact."),
            ],

            'contract_duration_months' => ['bail', 'required_if:is_contract_duration_checked,true'],
            'opportunity_start_date' => ['bail', 'required_if:is_contract_duration_checked,false'],
            'opportunity_end_date' => ['bail', 'required_if:is_contract_duration_checked,false'],
            'opportunity_suppliers' => ['bail', 'array', 'min:1'],
        ];
    }

    protected function getMessages(): array
    {
        return [
            'primary_account.required' => 'Primary Account must be chosen.',
            'primary_account.email.required' => 'Primary Account does not have a valid email.',
            'primary_account.email.email' => 'Primary Account does not have a valid email.',
            'primary_account.vat_type.required' => 'Primary Account does not have a tax type.',
            'primary_account.vat.required_if' => 'Primary Account must have a VAT number.',
            'primary_account.vendors.required' => 'Primary Account is not associated with any vendor.',
            'primary_account.addresses.required' => 'Primary Account does not have any address.',
            'primary_account.contacts.required' => 'Primary Account does not have any contact.',

            'primary_account_contact.required' => 'Primary Contact must be chosen.',
            'primary_account_contact.email.required' => 'Primary Contact does not contain a valid email.',
            'primary_account_contact.email.email' => 'Primary Contact does not contain a valid email.',

            'end_user.required' => 'End Customer must be chosen.',
            'end_user.email.required' => 'End Customer does not have a valid email.',
            'end_user.email.email' => 'End Customer does not have a valid email.',
            'end_user.vat_type.required' => 'End Customer does not have a tax type.',
            'end_user.vat.required_if' => 'End Customer must have a VAT number.',
            'end_user.addresses.required' => 'End Customer does not have any address.',
            'end_user.contacts.required' => 'End Customer does not have any contact.',

            'contract_duration_months.required_if' => 'Support Duration must be selected.',
            'opportunity_start_date.required_if' => 'Start Date must be selected.',
            'opportunity_end_date.required_if' => 'End Date must be selected.',

            'opportunity_suppliers.min' => 'At least :min Supplier must be added.',
        ];
    }
}