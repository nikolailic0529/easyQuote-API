<?php

namespace App\DTO\Attachment;

use App\Enum\AttachmentType;
use App\Foundation\File\BinaryFileContent;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

final class CreateAttachmentData extends Data
{
    public function __construct(
        public readonly UploadedFile|BinaryFileContent $file,
        public readonly AttachmentType $type,
        public readonly bool $isDeleteProtected = false,
    ) {
    }
}