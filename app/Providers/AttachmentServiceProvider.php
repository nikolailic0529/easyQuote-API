<?php

namespace App\Providers;

use App\Services\Attachment\AttachmentDataMapper;
use App\Services\Attachment\AttachmentEntityService;
use App\Services\Attachment\AttachmentFileService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\ServiceProvider;

class AttachmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when([
            AttachmentEntityService::class,
            AttachmentDataMapper::class,
            AttachmentFileService::class,
        ])->needs(FilesystemAdapter::class)->give(function (Container $container) {
            return $container['filesystem']->disk('attachments');
        });
    }
}
