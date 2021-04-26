<?php

namespace App\Http\Requests\SalesOrderTemplate;

use App\DTO\SalesOrderTemplate\UpdateSchemaOfSalesOrderTemplateData;
use App\Models\Template\QuoteTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchemaOfSalesOrderTemplate extends FormRequest
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
                'bail', 'required', 'array'
            ],
            'data_headers' => [
                'bail', 'required', 'array'
            ],
            'data_headers.*.key' => [
                'required', 'string',
                Rule::in(array_keys(__('template.contract_data_headers'))),
            ],
            'data_headers.*.value' => [
                'required', 'string', 'filled',
            ],
        ];
    }

    /**
     * @return \App\DTO\SalesOrderTemplate\UpdateSchemaOfSalesOrderTemplateData
     */
    public function getUpdateSchemaOfSalesOrderTemplateData(): UpdateSchemaOfSalesOrderTemplateData
    {
        return $this->updateSchemaOfSalesOrderTemplateData ??= new UpdateSchemaOfSalesOrderTemplateData([
            'form_data' => $this->input('form_data'),
            'data_headers' => $this->input('data_headers')
        ]);
    }
}
