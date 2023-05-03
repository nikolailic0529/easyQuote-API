<?php

namespace App\Domain\Attachment\Providers;

use App\Domain\Attachment\Contracts\AttachmentHasher;
use App\Domain\Attachment\Services\AttachmentDataMapper;
use App\Domain\Attachment\Services\AttachmentEntityService;
use App\Domain\Attachment\Services\AttachmentFileService;
use App\Domain\Attachment\Services\AttachmentHashingService;
use App\Domain\Attachment\Services\AttachmentMd5Hasher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\ServiceProvider;

class AttachmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AttachmentHasher::class, AttachmentMd5Hasher::class);

        $this->app->when([
            AttachmentEntityService::class,
            AttachmentDataMapper::class,
            AttachmentFileService::class,
            AttachmentHashingService::class,
        ])->needs(FilesystemAdapter::class)->give(function (Container $container) {
            return $container['filesystem']->disk('attachments');
        });
    }
}
