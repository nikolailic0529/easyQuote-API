<?php

namespace App\Domain\QuoteFile\Requests;

use App\Domain\Settings\Facades\Setting;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteFileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quote_file' => [
                'required',
                'file',
                'mimes:'.Setting::get('supported_file_types_request'),
                'min:1',
                'max:'.Setting::get('file_upload_size_kb'),
            ],
            'file_type' => 'required|string|in:Distributor Price List,Payment Schedule',
        ];
    }

    public function messages()
    {
        return [
            'quote_file.max' => 'The allowed file upload maximum size is :max kb.',
        ];
    }
}
