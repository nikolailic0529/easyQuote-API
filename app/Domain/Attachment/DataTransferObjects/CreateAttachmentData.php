<?php

namespace App\Domain\Attachment\DataTransferObjects;

use App\Domain\Attachment\Enum\AttachmentType;
use App\Foundation\Filesystem\BinaryFileContent;
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
