<?php

namespace App\Http\Requests\SalesOrder;

use App\DTO\SalesOrder\UpdateSalesOrderData;
use App\Enum\VAT;
use App\Models\Template\SalesOrderTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrder extends FormRequest
{
    protected ?UpdateSalesOrderData $updateSalesOrderData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'sales_order_template_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(SalesOrderTemplate::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at'),
            ],
            'vat_number' => [
                'bail', Rule::requiredIf($this->input('vat_type') === VAT::VAT_NUMBER),
                'nullable', 'string', 'max:191',
            ],
            'vat_type' => [
                'bail', 'required', 'string',
                Rule::in(VAT::getValues()),
            ],
            'customer_po' => [
                'bail', 'required', 'string', 'filled', 'max:191',
            ],
            'contract_number' => [
                'bail', 'nullable', 'string', 'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'contract_number.max' => 'Contract number/SAID cannot be greater than :max characters.',
        ];
    }

    public function getUpdateSalesOrderData(): UpdateSalesOrderData
    {
        return $this->updateSalesOrderData ??= new UpdateSalesOrderData([
            'sales_order_template_id' => $this->input('sales_order_template_id'),
            'vat_number' => $this->input('vat_number'),
            'vat_type' => $this->input('vat_type'),
            'customer_po' => $this->input('customer_po'),
            'contract_number' => $this->input('contract_number'),
        ]);
    }
}
