<?php

namespace App\Domain\Worldwide\Services\Opportunity;

use App\Domain\Company\Enum\CompanyType;
use App\Domain\Company\Models\Company;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Validation\Rules\ValidSupplierData;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Rule;

class ImportedOpportunityDataValidator
{
    const IGNORE_MISSING_ACC_DATA = 1 << 0;

    public static int $flags = 0;

    public function __construct(protected Factory $validatorFactory)
    {
    }

    public static function getFlag(int $flag): bool
    {
        return (static::$flags & $flag) === $flag;
    }

    public static function setFlag(int $flag, bool $value): bool
    {
        if ($value) {
            static::$flags |= $flag;
        } else {
            static::$flags &= ~$flag;
        }

        return (static::$flags & $flag) === $flag;
    }

    public static function resetFlags(): void
    {
        static::$flags = 0;
    }

    public function __invoke(
        array $row,
        array $accountsDataDictionary,
        array $accountContactsDataDictionary
    ): MessageBag {
        return tap(new MessageBag(),
            function (MessageBag $errors) use ($row, $accountsDataDictionary, $accountContactsDataDictionary) {
                $validator = $this->validatorFactory->make(data: $row, rules: $this->getRules(), messages: $this->getMessages());

                $errors->merge($validator->errors());

                $accountName = $row['primary_account_name'] ?? null;

                if (is_null($accountName)) {
                    return;
                }

                $accountNameHash = md5($accountName);

                $accountExists = Company::query()
                    ->where('name', trim($accountName))
                    ->where('type', CompanyType::EXTERNAL)
                    ->exists();

                $accountData = $accountsDataDictionary[$accountNameHash] ?? null;
                $accountContactData = $accountContactsDataDictionary[$accountNameHash] ?? null;

                if ($accountExists === true || static::getFlag(self::IGNORE_MISSING_ACC_DATA)) {
                    return;
                }

                if (is_null($accountData)) {
                    $errors->add('primary_account_data',
                        "No data provided for a new Primary Account, Name: '$accountName'.");
                }
            });
    }

    protected function getRules(): array
    {
        return [
            'project_name' => [
                'bail', 'required', 'string', 'max:100',
                Rule::unique(Opportunity::class)
                    ->withoutTrashed(),
            ],
            'primary_account_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'primary_contact_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'nature_of_service' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'ren_month' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'start_date' => [
                'bail', 'nullable', 'date',
            ],
            'ren_year' => [
                'bail', 'nullable', 'integer',
            ],
            'end_date' => [
                'bail', 'nullable', 'date',
            ],
            'customer_status' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'business_partner_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'enduser' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'hw_status' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'region' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'opportunity_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'opportunity_value_foreign_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'opportunity_value_base_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'opportunity_value_currency_code' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'list_price' => [
                'bail', 'nullable', 'numeric',
            ],
            'list_price_foreign_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'list_price_base_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'list_price_currency_code' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'purchase_price' => [
                'bail', 'nullable', 'numeric',
            ],
            'purchase_price_foreign_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'purchase_price_base_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'purchase_price_currency_code' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'ranking' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:1',
            ],
            'personal_rating' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'margin' => [
                'bail', 'nullable', 'numeric',
            ],
            'account_manager' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'closing_date' => [
                'bail', 'required', 'date',
            ],
            'notes' => [
                'bail', 'nullable', 'string', 'max:10000',
            ],
            'sla' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'competition' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'lead_source' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'campaign' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'sales_unit' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'drop_in' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'delayed_closing' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'estimated_upsell_amount' => [
                'bail', 'nullable', 'numeric',
            ],
            'remark' => [
                'bail', 'nullable', 'string', 'max:10000',
            ],
            'higher_sla' => [
                'bail', 'nullable', 'string', 'in:Yes,No',
            ],
            'additional_hardware' => [
                'bail', 'nullable', 'string', 'in:Yes,No',
            ],
            'multi_year' => [
                'bail', 'nullable', 'string', 'in:Yes,No',
            ],
            'service_credits' => [
                'bail', 'nullable', 'string', 'in:Yes,No',
            ],
            'suppliers' => [
                'array',
            ],
            'suppliers.*' => [
                ValidSupplierData::new()
                    ->setSupplierNameAttribute('supplier')
                    ->setCountryNameAttribute('country')
                    ->allowBlankValues(),
            ],
            'suppliers.*.country' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'suppliers.*.supplier' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'suppliers.*.contact_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'suppliers.*.email_address' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'owner' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'sales_step' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'pipeline' => [
                'bail', 'required', 'string', 'max:191',
            ],
        ];
    }

    protected function getMessages(): array
    {
        return [
            'project_name.required' => 'The opportunity name is required.',
            'project_name.unique' => 'The opportunity name [:input] already taken.',
            'project_name.max' => 'The opportunity name can not be greater than :max characters.',
        ];
    }
}
