<?php

namespace App\Services\Opportunity;

use App\Enum\VAT;
use App\Models\Opportunity;
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
            ]);

            $validator = $this->validatorFactory->make(data: $opportunity->toArray(), rules: $this->getRules(), messages: $this->getMessages());

            $validator->passes();

            $errors->merge($validator->getMessageBag());
        });
    }

    protected function getRules(): array
    {
        return [
            'primary_account' => ['bail', 'required'],
            'primary_account.email' => ['exclude_without:primary_account', 'bail', 'required', 'email'],
            'primary_account.vat' => ['exclude_without:primary_account', 'bail', 'required_if:primary_account.vat_type,'.VAT::VAT_NUMBER],
            'primary_account.vendors' => ['exclude_without:primary_account', 'bail', 'required'],
            'primary_account.addresses' => ['exclude_without:primary_account', 'bail', 'required'],
            'primary_account.contacts' => ['exclude_without:primary_account', 'bail', 'required'],

            'primary_account_contact' => ['bail', 'required'],
            'primary_account_contact.email' => ['exclude_without:primary_account_contact', 'bail', 'required', 'email'],

            'end_user' => ['bail', 'required'],
            'end_user.email' => ['exclude_without:end_user', 'bail', 'required', 'email'],
            'end_user.vat' => ['exclude_without:end_user', 'bail', 'required_if:end_user.vat_type,'.VAT::VAT_NUMBER],
            'end_user.addresses' => ['exclude_without:end_user', 'bail', 'required'],
            'end_user.contacts' => ['exclude_without:end_user', 'bail', 'required'],
        ];
    }

    protected function getMessages(): array
    {
        return [
            'primary_account.required' => 'Primary Account must be chosen.',
            'primary_account.email.required' => 'Primary Account does not have a valid email.',
            'primary_account.email.email' => 'Primary Account does not have a valid email.',
            'primary_account.vat.required_if' => 'Primary Account must have a VAT number.',
            'primary_account.vendors.required' => 'Primary Account is not associated with any vendor.',
            'primary_account.addresses.required' => 'Primary Account does not have any address.',
            'primary_account.contacts.required' => 'Primary Account does not have any contact.',

            'primary_account_contact.required' => 'Primary Account Contact must be chosen.',
            'primary_account_contact.email.required' => 'Primary Account Contact does not contain a valid email.',
            'primary_account_contact.email.email' => 'Primary Account Contact does not contain a valid email.',

            'end_user.required' => 'End Customer must be chosen.',
            'end_user.email.required' => 'End Customer does not have a valid email.',
            'end_user.email.email' => 'End Customer does not have a valid email.',
            'end_user.vat.required_if' => 'End Customer must have a VAT number.',
            'end_user.addresses.required' => 'End Customer does not have any address.',
            'end_user.contacts.required' => 'End Customer does not have any contact.',

        ];
    }
}