<?php

namespace App\Http\Requests\Attachment;

use App\Models\Attachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAttachmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => ['required', 'filled', 'string', Rule::in(Attachment::TYPES)],
            'file' => ['required', 'file', 'mimes:txt,csv,pdf,xlsx,docx,jpeg,jpg,png,gif,svg,webp', 'max:'.setting('file_upload_size_kb')]
        ];
    }
}
