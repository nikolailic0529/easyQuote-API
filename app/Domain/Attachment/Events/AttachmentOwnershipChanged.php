<?php

namespace App\Domain\Attachment\Events;

use App\Domain\Attachment\Models\Attachment;
use Illuminate\Database\Eloquent\Model;

final class AttachmentOwnershipChanged
{
    public bool $afterCommit = true;

    public function __construct(
        public readonly Attachment $attachment,
        public readonly Attachment $oldAttachment,
        protected readonly ?Model $causer = null
    ) {
    }
}
