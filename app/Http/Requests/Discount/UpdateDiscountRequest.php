<?php namespace App\Http\Requests\Discount;

use Illuminate\Foundation\Http\FormRequest;

abstract class UpdateDiscountRequest extends FormRequest
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
                'string',
                'max:60'
            ],
            'country_id' => [
                'uuid',
                'exists:countries,id'
            ],
            'vendor_id' => [
                'uuid',
                'exists:vendors,id'
            ]
        ];
    }

    abstract public function additionalRules();
}
