<?php

namespace App\Domain\Attachment\Contracts;

interface AttachmentHasher
{
    public function hash(string $filepath): string;
}
