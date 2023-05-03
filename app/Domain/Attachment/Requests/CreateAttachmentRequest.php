<?php

namespace App\Domain\Attachment\Requests;

use App\Domain\Attachment\Enum\AttachmentType;
use App\Domain\Attachment\Validation\Rules\UniqueAttachmentHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Enum;

class CreateAttachmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'filled',
                'string',
                new Enum(AttachmentType::class),
            ],
            'file' => [
                'required',
                'file',
                'mimes:txt,csv,pdf,xlsx,docx,jpeg,jpg,png,gif,svg,webp',
                'max:'.setting('file_upload_size_kb'),
            ],
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

    public function validateFileHash(Model $model): void
    {
        $rule = $this->container->make(UniqueAttachmentHash::class);

        $this->validate([
            'file' => $rule->for($model),
        ]);
    }
}
