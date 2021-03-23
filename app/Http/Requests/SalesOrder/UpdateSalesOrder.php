<?php

namespace App\Http\Requests\SalesOrder;

use App\DTO\SalesOrder\UpdateSalesOrderData;
use App\Enum\VAT;
use App\Models\Template\ContractTemplate;
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
    public function rules()
    {
        return [
            'contract_template_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(ContractTemplate::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')
            ],
            'vat_number' => [
                'bail', Rule::requiredIf($this->input('vat_type') === VAT::VAT_NUMBER),
                'nullable', 'string', 'max:191'
            ],
            'vat_type' => [
                'bail', 'required', 'string',
                Rule::in(VAT::getValues())
            ],
            'customer_po' => [
                'bail', 'required', 'string', 'filled', 'max:191'
            ]
        ];
    }

    public function getUpdateSalesOrderData(): UpdateSalesOrderData
    {
        return $this->updateSalesOrderData ??= new UpdateSalesOrderData([
            'contract_template_id' => $this->input('contract_template_id'),
            'vat_number' => $this->input('vat_number'),
            'vat_type' => $this->input('vat_type'),
            'customer_po' => $this->input('customer_po')
        ]);
    }
}
