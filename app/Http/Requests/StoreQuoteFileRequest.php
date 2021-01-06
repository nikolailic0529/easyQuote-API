<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Facades\Setting;

class StoreQuoteFileRequest extends FormRequest
{
    protected $supported_file_types;

    protected $file_upload_size_kb;

    public function __construct()
    {
        $this->supported_file_types = Setting::get('supported_file_types_request');
        $this->file_upload_size_kb = Setting::get('file_upload_size_kb');
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
                'min:1',
                "max:{$this->file_upload_size_kb}",
            ],
            'file_type' => 'required|string|in:Distributor Price List,Payment Schedule'
        ];
    }

    public function messages()
    {
        return [
            'quote_file.max' => "The allowed file upload maximum size is :max kb."
        ];
    }
}
