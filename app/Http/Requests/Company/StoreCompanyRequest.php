<?php

namespace App\Http\Requests\Company;

use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    use PreparesNullValues;

    /**
     * Company types
     *
     * @var string
     */
    protected $types;

    /**
     * Company categories
     *
     * @var string
     */
    protected $categories;

    public function __construct()
    {
        $this->types = collect(__('company.types'))->implode(',');
        $this->categories = collect(__('company.categories'))->implode(',');
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'name' => [
                'required',
                'string',
                'max:60',
                'min:2',
                Rule::unique('companies')->whereNull('deleted_at')
            ],
            'vat' => [
                'required',
                'string',
                'max:60',
                'min:2',
                Rule::unique('companies')->whereNull('deleted_at')
            ],
            'type' => [
                'required',
                'string',
                'in:' . $this->types
            ],
            'logo' => [
                'image',
                'max:2048'
            ],
            'category' => [
                'nullable',
                'required_if:type,External',
                'string',
                'in:' . $this->categories
            ],
            'email' => 'required|email',
            'phone' => 'nullable|string|min:4|phone',
            'website' => 'nullable|string|min:4',
            'vendors' => 'array',
            'vendors.*' => 'required|uuid|exists:vendors,id',
            'default_vendor_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::in($this->vendors)
            ],
            'default_country_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_vendor', 'country_id')->where('vendor_id', $this->default_vendor_id)
            ],
            'default_template_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_quote_template', 'quote_template_id')->where('country_id', $this->default_country_id)
            ]
        ];
    }

    public function messages()
    {
        return [
            'name.exists' => CPE_01,
            'vat.exists' => CPE_01
        ];
    }

    protected function nullValues()
    {
        return ['phone', 'website'];
    }
}
