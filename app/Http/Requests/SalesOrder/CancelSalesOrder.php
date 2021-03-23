<?php

namespace App\Http\Requests\SalesOrder;

use App\DTO\SalesOrder\CancelSalesOrderData;
use Illuminate\Foundation\Http\FormRequest;

class CancelSalesOrder extends FormRequest
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
                'bail', 'required', 'string', 'max:500'
            ]
        ];
    }

    /**
     * @return CancelSalesOrderData|null
     */
    public function getCancelSalesOrderData(): CancelSalesOrderData
    {
        return $this->cancelSalesOrderData ??= new CancelSalesOrderData([
            'status_reason' => $this->input('status_reason')
        ]);
    }
}
