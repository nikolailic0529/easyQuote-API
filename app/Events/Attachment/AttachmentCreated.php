<?php

namespace App\Events\Attachment;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AttachmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(protected Attachment $attachment,
                                protected Model      $entity,
                                protected ?Model     $causer = null)
    {
    }

    public function getAttachment(): Attachment
    {
        return $this->attachment;
    }

    public function getParentEntity(): Model
    {
        return $this->entity;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}
