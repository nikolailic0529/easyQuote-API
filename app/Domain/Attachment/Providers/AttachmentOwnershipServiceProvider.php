<?php

namespace App\Domain\Attachment\Providers;

use App\Domain\Attachment\Services\AttachmentOwnershipService;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use Illuminate\Support\ServiceProvider;

class AttachmentOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(AttachmentOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}
