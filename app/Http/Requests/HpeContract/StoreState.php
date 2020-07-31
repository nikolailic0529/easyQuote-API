<?php

namespace App\Http\Requests\HpeContract;

use App\Models\HpeContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreState extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id'            => ['nullable', 'uuid', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'country_id'            => ['nullable', 'uuid', Rule::exists('countries', 'id')->whereNull('deleted_at')],
            'quote_template_id'     => ['nullable', 'uuid', Rule::exists('quote_templates', 'id')->whereNull('deleted_at')],
            'hpe_contract_file_id'  => ['nullable', 'uuid', Rule::exists('hpe_contract_files', 'id')->whereNull('deleted_at')],

            'sold_contact'           => ['nullable', 'array'],
            'sold_contact.org_name'  => ['required_with:sold_contact', 'string', 'max:191'],
            'sold_contact.attn'      => ['nullable', 'string', 'max:191'],
            'sold_contact.address'   => ['nullable', 'string', 'max:500'],
            'sold_contact.email'     => ['nullable', 'string', 'max:191'],
            'sold_contact.phone'     => ['nullable', 'string', 'max:191'],
            'sold_contact.post_code' => ['required_with:sold_contact', 'string', 'max:191'],
            'sold_contact.country'   => ['nullable', 'string', 'max:191'],
            'sold_contact.city'      => ['required_with:sold_contact', 'string', 'max:191'],

            'bill_contact'           => ['nullable', 'array'],
            'bill_contact.org_name'  => ['required_with:bill_contact', 'string', 'max:191'],
            'bill_contact.attn'      => ['nullable', 'string', 'max:191'],
            'bill_contact.email'     => ['nullable', 'string', 'max:191'],
            'bill_contact.phone'     => ['nullable', 'string', 'max:191'],
            'bill_contact.address'   => ['nullable', 'string', 'max:500'],
            'bill_contact.post_code' => ['required_with:bill_contact', 'string', 'max:191'],
            'bill_contact.country'   => ['nullable', 'string', 'max:191'],
            'bill_contact.city'      => ['required_with:bill_contact', 'string', 'max:191'],

            'purchase_order_no'      => ['nullable', 'string', 'max:191'],
            'hpe_sales_order_no'     => ['nullable', 'string', 'max:191'],
            'purchase_order_date'    => ['nullable', 'date'],

            'hw_delivery_contact'           => ['nullable', 'array'],
            'hw_delivery_contact.attn'      => ['required_with:hw_delivery_contact', 'string', 'max:191'],
            'hw_delivery_contact.phone'     => ['nullable', 'string', 'max:191'],
            'hw_delivery_contact.email'     => ['nullable', 'string', 'max:191'],

            'sw_delivery_contact'           => ['nullable', 'array'],
            'sw_delivery_contact.attn'      => ['required_with:sw_delivery_contact', 'string', 'max:191'],
            'sw_delivery_contact.phone'     => ['nullable', 'string', 'max:191'],
            'sw_delivery_contact.email'     => ['nullable', 'string', 'max:191'],

            'pr_support_contact'           => ['nullable', 'array'],
            'pr_support_contact.attn'      => ['required_with:pr_support_contact', 'string', 'max:191'],
            'pr_support_contact.phone'     => ['nullable', 'string', 'max:191'],
            'pr_support_contact.email'     => ['nullable', 'string', 'max:191'],

            'entitled_party_contact'                 => ['nullable', 'array'],
            'entitled_party_contact.org_name'        => ['required_with:entitled_party_contact', 'string', 'max:191'],
            'entitled_party_contact.address'         => ['required_with:entitled_party_contact', 'string', 'max:500'],
            'entitled_party_contact.post_code'       => ['nullable', 'string', 'max:191'],
            'entitled_party_contact.city'            => ['nullable', 'string', 'max:191'],
            'entitled_party_contact.country'         => ['nullable', 'string', 'max:191'],

            'end_customer_contact'                   => ['nullable', 'array'],
            'end_customer_contact.org_name'          => ['required_with:end_customer_contact', 'string', 'max:191'],
            'end_customer_contact.address'           => ['required_with:end_customer_contact', 'string', 'max:500'],
            'end_customer_contact.post_code'         => ['nullable', 'string', 'max:191'],
            'end_customer_contact.city'              => ['nullable', 'string', 'max:191'],
            'end_customer_contact.country'           => ['nullable', 'string', 'max:191'],

            'additional_notes'      => ['nullable', 'string', 'max:20000'],

            'checkbox_status'       => ['nullable', 'array'],
            'last_drafted_step'     => ['string', Rule::in(array_keys(HpeContract::modelCompleteness()))],
            'contract_date'         => ['exclude_unless:last_drafted_step,Assorted Detail', 'date']
        ];
    }

    public function messages()
    {
        return [
            'company_id.required'        => 'Company must be selected.',
            'country_id.required'        => 'Country must be selected.',
            'quote_template_id.required' => 'Template must be selected.',
            'company_id.exists'          => 'Invalid Company selected.',
            'country_id.exists'          => 'Invalid Country selected.',
            'quote_template_id.exists'   => 'Invalid Template selected.',
        ];
    }
}
