<?php

namespace App\Http\Requests\Company;

use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
                'string',
                'max:60',
                'min:2',
                Rule::unique('companies')->whereNull('deleted_at')->ignore($this->company)
            ],
            'vat' => [
                'string',
                'max:60',
                'min:2',
                Rule::unique('companies')->whereNull('deleted_at')->ignore($this->company)
            ],
            'type' => [
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
            'email' => 'email',
            'phone' => 'nullable|string|min:4|phone',
            'website' => 'nullable|string|min:4',
            'vendors' => 'array',
            'vendors.*' => 'required|uuid|exists:vendors,id',
            'default_vendor_id' => [
                'nullable',
                'uuid',
                Rule::in($this->vendors ?? $this->company->vendors->pluck('id')->toArray())
            ],
            'default_country_id' => [
                'nullable',
                'string',
                'uuid',
                Rule::exists('country_vendor', 'country_id')->where('vendor_id', $this->default_vendor_id ?? $this->company->default_vendor_id)
            ],
            'addresses_attach' => 'nullable|array',
            'addresses_attach.*' => 'required|string|uuid|exists:addresses,id',
            'addresses_detach' => 'nullable|array',
            'addresses_detach.*' => 'required|string|uuid|exists:addresses,id',
            'contacts_attach' => 'nullable|array',
            'contacts_attach.*' => 'required|string|uuid|exists:contacts,id',
            'contacts_detach' => 'nullable|array',
            'contacts_detach.*' => 'required|string|uuid|exists:contacts,id',
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
