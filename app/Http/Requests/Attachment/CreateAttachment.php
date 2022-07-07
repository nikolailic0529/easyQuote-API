<?php

namespace App\Http\Requests\Attachment;

use App\Enum\AttachmentType;
use App\Models\Attachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateAttachment extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => ['required', 'filled', 'string', new Enum(AttachmentType::class)],
            'file' => ['required', 'file', 'mimes:txt,csv,pdf,xlsx,docx,jpeg,jpg,png,gif,svg,webp', 'max:'.setting('file_upload_size_kb')],
        ];
    }

    public function getUploadedFile(): UploadedFile
    {
        return $this->file('file');
    }

    public function getAttachmentType(): AttachmentType
    {
        return AttachmentType::from($this->input('type'));
    }
}
