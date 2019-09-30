<?php namespace App\Http\Requests\Margin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCountryMarginRequest extends FormRequest
{
    protected $types;

    protected $margin;

    public function __construct()
    {
        $this->types = collect(__('quote.types'))->implode(',');
        $this->margin['types'] = collect(__('margin.types'))->implode(',');
        $this->margin['methods'] = collect(__('margin.methods'))->implode(',');
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
            'is_fixed' => [
                'boolean'
            ],
            'vendor_id' => [
                'uuid',
                'exists:vendors,id'
            ],
            'country_id' => [
                'uuid',
                'exists:countries,id'
            ],
            'quote_type' => [
                'string',
                'in:' . $this->types
            ],
            'method' => [
                'string',
                'in:' . $this->margin['methods']
            ],
            'value' => [
                'numeric',
                $this->ifEquals('is_fixed', false, 'max:100')
            ]
        ];
    }

    public function ifEquals($anotherAttribute, $value, $rule)
    {
        if($this->input($anotherAttribute) == $value) {
            return $rule;
        }
    }
}
