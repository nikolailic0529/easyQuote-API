<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Setting, Arr;

class StoreQuoteFileRequest extends FormRequest
{
    protected $supported_file_types;

    public function __construct()
    {
        $this->supported_file_types = implode(',', Arr::lower(Setting::get('supported_file_types')));
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
            'quote_file' => [
                'required',
                'file',
                "mimes:{$this->supported_file_types}",
                'max:20000',
            ],
            'file_type' => 'required|string|in:Distributor Price List,Payment Schedule'
        ];
    }
}
