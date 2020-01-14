<?php namespace App\Http\Requests\Margin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCountryMarginRequest extends FormRequest
{
    /** @var string */
    protected $quoteTypes;

    /** @var string */
    protected $marginMethods;

    public function __construct()
    {
        $this->quoteTypes = collect(__('quote.types'))->implode(',');
        $this->marginMethods = collect(__('margin.methods'))->implode(',');
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
                'required',
                'boolean'
            ],
            'vendor_id' => [
                'required',
                'uuid',
                Rule::exists('country_vendor')
                    ->where('country_id', $this->country_id)
            ],
            'country_id' => [
                'required',
                'uuid',
                'exists:countries,id'
            ],
            'quote_type' => [
                'required',
                'string',
                'in:' . $this->quoteTypes
            ],
            'method' => [
                'required',
                'string',
                'in:' . $this->marginMethods
            ],
            'value' => [
                'required',
                'numeric',
                value(function () {
                    return $this->is_fixed == false ? 'max:100' : null;
                }),
                Rule::unique('country_margins')
                    ->where('country_id', $this->country_id)
                    ->where('vendor_id', $this->vendor_id)
                    ->where('is_fixed', $this->is_fixed)
                    ->where('method', $this->input('method'))
                    ->whereNull('deleted_at')
            ]
        ];
    }

    public function messages()
    {
        return [
            'vendor_id.exists' => 'The chosen vendor should belong to the chosen country.',
            'value.unique' => ME_01
        ];
    }
}
