<?php

namespace App\Domain\Attachment\Services;

use App\Domain\Attachment\Contracts\AttachmentHasher;

class AttachmentMd5Hasher implements AttachmentHasher
{
    public function hash(string $filepath): string
    {
        return md5_file($filepath);
    }
}
