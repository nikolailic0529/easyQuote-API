<?php

namespace App\Http\Requests\WorldwideQuote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UploadBatchAssetsFile extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => [
                'bail', 'required', 'file', 'mimes:xlsx', 'max:10000'
            ]
        ];
    }

    public function getFile(): UploadedFile
    {
        return $this->file('file');
    }
}
