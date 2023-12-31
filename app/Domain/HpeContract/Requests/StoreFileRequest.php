<?php

namespace App\Domain\HpeContract\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
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
            'file' => "required|file|mimes:csv,txt|max:{$max}",
        ];
    }
}
