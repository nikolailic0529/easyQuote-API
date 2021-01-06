<?php

namespace App\Http\Requests;

use App\Models\Quote\Quote;
use Illuminate\Foundation\Http\FormRequest;

class MappingReviewRequest extends FormRequest
{
    protected ?Quote $quote = null;

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
            'quote_id' => 'required|uuid|exists:quotes,id',
            'group_id' => 'nullable|string|uuid',
            'search' => 'string'
        ];
    }

    public function getQuote(): Quote
    {
        if (isset($this->quote)) {
            return $this->quote;
        }

        return $this->quote = Quote::findOrFail($this->quote_id);
    }
}
