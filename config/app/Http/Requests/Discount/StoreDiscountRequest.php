<?php namespace App\Http\Requests\Discount;

use Illuminate\Foundation\Http\FormRequest;

abstract class StoreDiscountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return array_merge($this->commonRules(), $this->additionalRules());
    }

    protected function commonRules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:60'
            ],
            'country_id' => [
                'required',
                'uuid',
                'exists:countries,id'
            ],
            'vendor_id' => [
                'required',
                'uuid',
                'exists:vendors,id'
            ]
        ];
    }

    abstract public function additionalRules();
}
