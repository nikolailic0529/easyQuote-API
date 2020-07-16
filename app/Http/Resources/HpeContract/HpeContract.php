<?php

namespace App\Http\Resources\HpeContract;

use Illuminate\Http\Resources\Json\JsonResource;

class HpeContract extends JsonResource
{
    public $availableIncludes = ['company', 'hpeContractTemplate', 'country', 'hpeContractFile', 'hpeContractData'];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'created_at' => carbon_format($this->created_at, config('date.format_time')),
            'updated_at' => carbon_format($this->updated_at, config('date.format_time')),
            'submitted_at' => $this->submitted_at,

            'hpe_contract_number' => $this->contract_number,

            'user_id' => $this->user_id,
            'quote_template_id' => $this->quote_template_id,
            'company_id' => $this->company_id,
            'country_id' => $this->country_id,

            'hpe_contract_file_id' => $this->hpe_contract_file_id,

            'hpe_contract_file' => $this->whenLoaded('hpeContractFile'),
            'hpe_contract_data' => $this->whenLoaded('hpeContractData'),

            'company' => $this->whenLoaded('company'),
            'country' => $this->whenLoaded('country'),
            'hpe_contract_template' => $this->whenLoaded('hpeContractTemplate'),

            'amp_id' => $this->amp_id,
            'support_account_reference' => $this->support_account_reference,
            'orders_authorization' => $this->orders_authorization,
            
            'contract_numbers' => $this->contract_numbers,
            'services'  => $this->services,

            'customer_name' => $this->customer_name,
            'customer_address' => $this->customer_address,
            'customer_city' => $this->customer_city,
            'customer_post_code' => $this->customer_post_code,
            'customer_country_code' => $this->customer_country_code,

            'purchase_order_no' => $this->purchase_order_no,
            'hpe_sales_order_no' => $this->hpe_sales_order_no,

            'purchase_order_date' => optional($this->purchase_order_date)->format(config('date.format_ui')),

            'customer_contacts' => [
                'sold_contact'  => $this->sold_contact,

                'bill_contact' => $this->bill_contact,

                'hw_delivery_contact' => $this->hw_delivery_contact,
              
                'sw_delivery_contact' => $this->sw_delivery_contact,
              
                'pr_support_contact' => $this->pr_support_contact,

                'entitled_party_contact' => $this->entitled_party_contact,

                'end_customer_contact' => $this->end_customer_contact,
            ],

            'last_drafted_step' => $this->last_drafted_step,
            'completeness' => $this->completeness,
            'last_drafted_step' => $this->last_drafted_step,
            'checkbox_status' => $this->checkbox_status,
            
            'contract_date' => optional($this->contract_date)->format(config('date.format_ui')),
        ];
    }
}
