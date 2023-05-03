<?php

namespace App\Domain\Worldwide\Requests\SalesOrder;

use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Cancel\CancelSalesOrderData;
use Illuminate\Foundation\Http\FormRequest;

class CancelSalesOrderRequest extends FormRequest
{
    protected ?CancelSalesOrderData $cancelSalesOrderData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status_reason' => [
                'bail', 'required', 'string', 'max:500',
            ],
        ];
    }

    /**
     * @return CancelSalesOrderData|null
     */
    public function getCancelSalesOrderData(): CancelSalesOrderData
    {
        return $this->cancelSalesOrderData ??= new CancelSalesOrderData([
            'sales_order_id' => $this->route('sales_order')->getKey(),
            'status_reason' => $this->input('status_reason'),
        ]);
    }
}
