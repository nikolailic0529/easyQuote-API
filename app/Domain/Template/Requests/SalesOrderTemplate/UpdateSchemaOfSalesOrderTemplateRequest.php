<?php

namespace App\Domain\Template\Requests\SalesOrderTemplate;

use App\Domain\Template\DataTransferObjects\TemplateDataHeader;
use App\Domain\Template\DataTransferObjects\UpdateSchemaOfSalesOrderTemplateData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchemaOfSalesOrderTemplateRequest extends FormRequest
{
    protected ?UpdateSchemaOfSalesOrderTemplateData $updateSchemaOfSalesOrderTemplateData;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'form_data' => [
                'bail', 'required', 'array',
            ],
            'data_headers' => [
                'bail', 'array',
            ],
            'data_headers.*.key' => [
                'required', 'string',
                Rule::in(array_keys(__('template.sales_order_data_headers'))),
            ],
            'data_headers.*.value' => [
                'required', 'string', 'filled',
            ],
        ];
    }

    public function getUpdateSchemaOfSalesOrderTemplateData(): UpdateSchemaOfSalesOrderTemplateData
    {
        return $this->updateSchemaOfSalesOrderTemplateData ??= value(function () {
            /** @var \App\Domain\Worldwide\Models\SalesOrderTemplate $salesOrderTemplate */
            $salesOrderTemplate = $this->route('sales_order_template');

            return new UpdateSchemaOfSalesOrderTemplateData([
                'form_data' => $this->input('form_data'),
                'data_headers' => array_values(array_map(function (array $dataHeader) {
                    return new TemplateDataHeader([
                        'key' => $dataHeader['key'],
                        'value' => $dataHeader['value'],
                    ]);
                }, $this->input('data_headers') ?? [])),
            ]);
        });
    }
}
