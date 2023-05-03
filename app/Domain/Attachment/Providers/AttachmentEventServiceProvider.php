<?php

namespace App\Domain\Attachment\Providers;

use App\Domain\Attachment\Listeners\AttachmentEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class AttachmentEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        AttachmentEventAuditor::class,
    ];
}
