<?php

namespace App\Http\Requests\Opportunity;

use App\DTO\Opportunity\UpdateOpportunityData;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContractType;
use App\Models\OpportunitySupplier;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateOpportunity extends FormRequest
{
    protected ?UpdateOpportunityData $updateOpportunityData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contract_type_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(ContractType::class, 'id')
            ],
            'primary_account_id' => [
                'bail', 'uuid',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at'),
            ],
            'primary_account_contact_id' => [
                'bail', 'uuid',
                Rule::exists(Contact::class, 'id')->whereNull('deleted_at'),
            ],
            'account_manager_id' => [
                'bail', 'uuid',
                Rule::exists(User::class, 'id')->whereNull('deleted_at'),
            ],
            'project_name' => [
                'bail', 'string', 'max:191',
            ],
            'nature_of_service' => [
                'bail', 'string', 'max:191',
            ],
            'renewal_month' => [
                'bail', 'string', 'max:191'
            ],
            'renewal_year' => [
                'bail', 'integer', 'max:9999',
            ],
            'customer_status' => [
                'bail', 'string', 'max:191',
            ],
            'end_user_name' => [
                'bail', 'string', 'max:191',
            ],
            'hardware_status' => [
                'bail', 'string', 'max:191',
            ],
            'region_name' => [
                'bail', 'string', 'max:191',
            ],
            'opportunity_start_date' => [
                'bail', 'required', 'date_format:Y-m-d',
            ],
            'opportunity_end_date' => [
                'bail', 'required', 'date_format:Y-m-d',
            ],
            'opportunity_closing_date' => [
                'bail', 'required', 'date_format:Y-m-d',
            ],
            'expected_order_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'customer_order_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'purchase_order_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'supplier_order_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'supplier_order_transaction_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'supplier_order_confirmation_date' => [
                'bail', 'date_format:Y-m-d',
            ],
            'opportunity_amount' => [
                'bail', 'numeric',
            ],
            'opportunity_amount_currency_code' => [
                'bail', 'string', 'size:3',
            ],
            'purchase_price' => [
                'bail', 'numeric',
            ],
            'purchase_price_currency_code' => [
                'bail', 'string', 'size:3',
            ],
            'list_price' => [
                'bail', 'numeric',
            ],
            'list_price_currency_code' => [
                'bail', 'string', 'size:3',
            ],
            'estimated_upsell_amount' => [
                'bail', 'numeric',
            ],
            'estimated_upsell_amount_currency_code' => [
                'bail', 'string', 'size:3',
            ],
            'personal_rating' => [
                'bail', 'nullable', 'string', 'filled', 'max:191',
            ],
            'margin_value' => [
                'bail', 'nullable', 'numeric',
            ],
            'service_level_agreement_id' => [
                'bail', 'string', 'max:191',
            ],
            'sale_unit_name' => [
                'bail', 'string', 'max:191',
            ],
            'competition_name' => [
                'bail', 'string', 'max:191',
            ],
            'drop_in' => [
                'bail', 'string', 'max:191',
            ],
            'lead_source_name' => [
                'bail', 'string', 'max:191',
            ],
            'has_higher_sla' => [
                'bail', 'boolean',
            ],
            'is_multi_year' => [
                'bail', 'boolean',
            ],
            'has_additional_hardware' => [
                'bail', 'boolean',
            ],
            'remarks' => [
                'bail', 'string', 'max:10000',
            ],
            'notes' => [
                'bail', 'string', 'max:10000',
            ],
            'sale_action_name' => [
                'bail', 'string',
            ],
            'ranking' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:1'
            ],
            'suppliers_grid' => [
                'bail', 'nullable', 'array',
            ],
            'suppliers_grid.*.id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(OpportunitySupplier::class, 'id')->where('opportunity_id', $this->route('opportunity')->getKey())
            ],
            'suppliers_grid.*.supplier_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'suppliers_grid.*.country_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'suppliers_grid.*.contact_name' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
            'suppliers_grid.*.contact_email' => [
                'bail', 'nullable', 'string', 'max:191',
            ],
        ];
    }

    public function getOpportunityData(): UpdateOpportunityData
    {
        return $this->updateOpportunityData ??= with(true, function () {
            $createSuppliers = [];
            $updateSuppliers = [];

            foreach ($this->input('suppliers_grid') ?? [] as $supplierData) {
                if (isset($supplierData['id'])) {
                    $updateSuppliers[] = [
                        'supplier_id' => $supplierData['id'],
                        'supplier_name' => $supplierData['supplier_name'] ?? null,
                        'country_name' => $supplierData['country_name'] ?? null,
                        'contact_name' => $supplierData['contact_name'] ?? null,
                        'contact_email' => $supplierData['contact_email'] ?? null
                    ];
                } else {
                    $createSuppliers[] = [
                        'supplier_name' => $supplierData['supplier_name'] ?? null,
                        'country_name' => $supplierData['country_name'] ?? null,
                        'contact_name' => $supplierData['contact_name'] ?? null,
                        'contact_email' => $supplierData['contact_email'] ?? null
                    ];
                }
            }

            return new UpdateOpportunityData([
                'contract_type_id' => $this->input('contract_type_id'),
                'account_manager_id' => $this->input('account_manager_id'),
                'primary_account_id' => $this->input('primary_account_id'),
                'primary_account_contact_id' => $this->input('primary_account_contact_id'),
                'project_name' => $this->input('project_name'),
                'nature_of_service' => $this->input('nature_of_service'),
                'renewal_month' => $this->input('renewal_month'),
                'renewal_year' => $this->input('renewal_year'),
                'customer_status' => $this->input('customer_status'),
                'end_user_name' => $this->input('end_user_name'),
                'hardware_status' => $this->input('hardware_status'),
                'region_name' => $this->input('region_name'),
                'opportunity_start_date' => transform($this->input('opportunity_start_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'opportunity_end_date' => transform($this->input('opportunity_end_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'opportunity_closing_date' => transform($this->input('opportunity_closing_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'expected_order_date' => transform($this->input('expected_order_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'customer_order_date' => transform($this->input('customer_order_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'purchase_order_date' => transform($this->input('purchase_order_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'supplier_order_date' => transform($this->input('supplier_order_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'supplier_order_transaction_date' => transform($this->input('supplier_order_transaction_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'supplier_order_confirmation_date' => transform($this->input('supplier_order_confirmation_date'), fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
                'opportunity_amount' => transform($this->input('opportunity_amount'), fn($value) => (float)$value),
                'opportunity_amount_currency_code' => $this->input('opportunity_amount_currency_code'),
                'purchase_price' => transform($this->input('purchase_price'), fn($value) => (float)$value),
                'purchase_price_currency_code' => $this->input('purchase_price_currency_code'),
                'list_price' => transform($this->input('list_price'), fn($value) => (float)$value),
                'list_price_currency_code' => $this->input('list_price_currency_code'),
                'estimated_upsell_amount' => transform($this->input('estimated_upsell_amount'), fn($value) => (float)$value),
                'estimated_upsell_amount_currency_code' => $this->input('estimated_upsell_amount_currency_code'),
                'personal_rating' => $this->input('personal_rating'),
                'ranking' => transform($this->input('ranking'), fn($value) => (float)$value),
                'margin_value' => transform($this->input('margin_value'), fn($value) => (float)$value),
                'service_level_agreement_id' => $this->input('service_level_agreement_id'),
                'sale_unit_name' => $this->input('sale_unit_name'),
                'competition_name' => $this->input('competition_name'),
                'drop_in' => $this->input('drop_in'),
                'lead_source_name' => $this->input('lead_source_name'),
                'has_higher_sla' => $this->boolean('has_higher_sla'),
                'is_multi_year' => $this->boolean('is_multi_year'),
                'has_additional_hardware' => $this->boolean('has_additional_hardware'),
                'remarks' => $this->input('remarks'),
                'notes' => $this->input('notes'),
                'sale_action_name' => $this->input('sale_action_name'),
                'create_suppliers' => $createSuppliers,
                'update_suppliers' => $updateSuppliers
            ]);

        });
    }
}
