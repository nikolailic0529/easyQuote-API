<?php

namespace App\Domain\Attachment\Services;

use App\Domain\Attachment\Contracts\AttachmentHasher;
use App\Domain\Attachment\Models\Attachment;
use Illuminate\Filesystem\FilesystemAdapter;

class AttachmentHashingService
{
    public function __construct(
        protected readonly FilesystemAdapter $filesystem,
        protected readonly AttachmentHasher $hasher,
    ) {
    }

    public function work(callable $onStart = null, callable $onProgress = null): void
    {
        $onProgress ??= static function (): void {
        };

        $model = new Attachment();

        if ($onStart) {
            $onStart($model->newQuery()->count());
        }

        $model->newQuery()
            ->lazy()
            ->each(function (Attachment $attachment) use ($onProgress): void {
                if (!$this->filesystem->exists($attachment->filepath)) {
                    return;
                }

                $attachment->md5_hash = $this->hasher->hash($this->filesystem->path($attachment->filepath));
                $attachment->save();

                $onProgress();
            });

    }
}
