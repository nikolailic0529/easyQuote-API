<?php

namespace App\Http\Requests\HpeContractFile;

use Illuminate\Foundation\Http\FormRequest;

class StoreFile extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $max = setting('file_upload_size_kb');

        return [
            'file' => "required|file|mimes:csv,txt|min:1|max:{$max}"
        ];
    }
}
