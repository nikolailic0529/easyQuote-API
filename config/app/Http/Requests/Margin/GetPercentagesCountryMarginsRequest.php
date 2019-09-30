<?php namespace App\Http\Requests\Margin;

use Illuminate\Foundation\Http\FormRequest;

class GetPercentagesCountryMarginsRequest extends FormRequest
{

    protected $quoteTypes;

    protected $margin;

    public function __construct()
    {
        $this->quoteTypes = collect(__('quote.types'))->implode(',');
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
            'quote_type' => [
                'required',
                'string',
                'in:' . $this->quoteTypes
            ],
            'method' => [
                'required',
                'in:' . $this->margin['methods']
            ],
            'quote_id' => [
                'required',
                'exists:quotes,id'
            ]
        ];
    }
}
