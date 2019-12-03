<?php

namespace App\Http\Requests\Company;

use App\Traits\Request\PreparesNullValues;
use Illuminate\Foundation\Http\FormRequest;

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
            'name' => 'string|max:60|min:2',
            'vat' => 'string|max:60|min:2',
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
            'phone' => 'nullable|string|min:4',
            'website' => 'nullable|string|min:4',
            'vendors' => 'array',
            'vendors.*' => 'required|uuid|exists:vendors,id',
            'default_vendor_id' => 'nullable|uuid|exists:vendors,id',
            'addresses_attach' => 'nullable|array',
            'addresses_attach.*' => 'required|string|uuid|exists:addresses,id',
            'addresses_detach' => 'nullable|array',
            'addresses_detach.*' => 'required|string|uuid|exists:addresses,id',
            'contacts_attach' => 'nullable|array',
            'contacts_attach.*' => 'required|string|uuid|exists:contacts,id',
            'contacts_detach.*' => 'required|string|uuid|exists:contacts,id',
        ];
    }

    protected function nullValues()
    {
        return ['phone', 'website'];
    }

    protected function passedValidation()
    {
        if ($this->filled(['addresses_attach', 'addresses_detach'])) {
            $addresses_attach = array_diff($this->addresses_attach, $this->addresses_detach);
            $this->merge(compact('addresses_attach'));
        }

        if ($this->filled(['contacts_attach', 'contacts_detach'])) {
            $contacts_attach = array_diff($this->contacts_attach, $this->contacts_detach);
            $this->merge(compact('contacts_attach'));
        }
    }
}
