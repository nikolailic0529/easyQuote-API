<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Settings\Facades\Setting;
use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleFileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $supportedFileTypes = Setting::get('supported_file_types_request');
        $maxFileSize = Setting::get('file_upload_size_kb');

        return [
            'file' => "bail|required|file|mimes:$supportedFileTypes|min:1|max:$maxFileSize",
        ];
    }

    public function messages()
    {
        return [
            'file.max' => 'The allowed file upload maximum size is :max kb.',
            'file.mimes' => 'Unsupported file extension.',
        ];
    }
}
