<?php

namespace App\Domain\HpeContract\Requests;

use App\Domain\HpeContract\Models\HpeContract;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $hpeContract = $this->route('hpe_contract');

        if (!$hpeContract instanceof HpeContract) {
            return;
        }

        $validator = validator(
            [
                'contract_date' => $hpeContract->contract_date,
                'company_id' => $hpeContract->company_id,
                'country_id' => $hpeContract->country_id,
                'quote_template_id' => $hpeContract->quote_template_id,
                'hpe_contract_file_id' => $hpeContract->hpe_contract_file_id,
                'selected_assets' => $hpeContract->hpeContractData()->whereIsSelected(true)->count(),
            ],
            [
                'contract_date' => 'filled',
                'company_id' => 'filled|uuid',
                'country_id' => 'filled|uuid',
                'quote_template_id' => 'filled|uuid',
                'hpe_contract_file_id' => 'filled|uuid',
                'selected_assets' => 'integer|min:1',
                'purchase_order_no' => 'filled',
                'hpe_sales_order_no' => 'filled',
                'purchase_order_date' => 'filled',
            ],
            [
                'contract_date.*' => 'Contract Date must be specified before submit.',
                'company_id.*' => 'Contract Company must be specified before submit.',
                'country_id.*' => 'Contract Country must be specified before submit.',
                'quote_template_id.*' => 'Contract Template must be specified before submit.',
                'hpe_contract_file_id.*' => 'Contract File must be imported before submit.',
                'selected_assets.*' => 'At least one Asset must be selected to submit the Contract.',
                'purchase_order_no.*' => 'Purchase Order No must be specified before submit.',
                'hpe_sales_order_no.*' => 'HPE Sales Order No must be specified before submit.',
                'purchase_order_date.*' => 'Purchase Order Date must be specified before submit.',
            ]
        );

        $validator->validate();

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }
}
