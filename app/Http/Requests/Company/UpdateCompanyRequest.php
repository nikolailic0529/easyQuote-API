<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
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
            'default_vendor_id' => 'nullable|uuid|exists:vendors,id'
        ];
    }
}
