<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteFileRequest extends FormRequest
{
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
            'quote_file' => 'required|file|mimes:pdf,csv,txt,xlsx,xls|max:20000',
            'file_type' => 'required|string|in:Distributor Price List,Payment Schedule'
        ];
    }
}
