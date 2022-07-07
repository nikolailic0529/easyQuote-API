<?php

namespace App\Http\Resources\V1\SalesOrderTemplate;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderTemplateWithIncludes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Template\SalesOrderTemplate|\App\Http\Resources\SalesOrderTemplate\SalesOrderTemplateWithIncludes $this */

        $dataHeaders = value(function (): array {
            /** @var \App\Models\Template\SalesOrderTemplate|\App\Http\Resources\SalesOrderTemplate\SalesOrderTemplateWithIncludes $this */

            $headers = [];

            foreach (__('template.sales_order_data_headers') as $key => $header) {
                $headers[$key] = [
                    'key' => $key,
                    'label' => $header['label'],
                    'value' => $this->templateSchema->data_headers[$key] ?? $header['value'],
                ];
            }

            return $headers;
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_system' => (bool)$this->is_system,
            'company_id' => $this->company_id,
            'vendor_id' => $this->vendor_id,
            'currency_id' => $this->currency_id,
            'business_division_id' => $this->business_division_id,
            'contract_type_id' => $this->contract_type_id,
            'company' => [
                'id' => $this->company->getKey(),
                'name' => $this->company->name,
            ],
            'vendor' => [
                'id' => $this->vendor->getKey(),
                'name' => $this->vendor->name,
            ],
            'currency' => $this->currency,
            'form_data' => $this->templateSchema->form_data,
            'data_headers' => array_values($dataHeaders),
            'data_headers_keyed' => $dataHeaders,
            'countries' => $this->countries->map->only(['id', 'name']),
            'created_at' => (string)$this->created_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
