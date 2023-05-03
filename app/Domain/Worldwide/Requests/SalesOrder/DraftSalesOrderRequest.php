<?php

namespace App\Domain\Worldwide\Requests\SalesOrder;

use App\Domain\Company\Enum\VAT;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\DraftSalesOrderData;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DraftSalesOrderRequest extends FormRequest
{
    protected ?DraftSalesOrderData $draftSalesOrderData = null;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'worldwide_quote_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideQuote::class, 'id')->whereNull('deleted_at')->whereNotNull('submitted_at'),
                Rule::unique(SalesOrder::class, 'worldwide_quote_id')->whereNull('deleted_at'),
            ],
            'sales_order_template_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(SalesOrderTemplate::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at'),
            ],
            'vat_number' => [
                'bail',
                Rule::requiredIf($this->input('vat_type') === VAT::VAT_NUMBER),
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
            'worldwide_quote_id.unique' => 'Sales Order already exists for the given Quote',
            'contract_number.max' => 'Contract number/SAID cannot be greater than :max characters.',
        ];
    }

    public function getDraftSalesOrderData(): DraftSalesOrderData
    {
        return $this->draftSalesOrderData ??= new DraftSalesOrderData([
            'user_id' => $this->user()->getKey(),
            'worldwide_quote_id' => $this->input('worldwide_quote_id'),
            'sales_order_template_id' => $this->input('sales_order_template_id'),
            'vat_number' => $this->input('vat_number'),
            'vat_type' => $this->input('vat_type'),
            'customer_po' => $this->input('customer_po'),
            'contract_number' => $this->input('contract_number'),
        ]);
    }
}
