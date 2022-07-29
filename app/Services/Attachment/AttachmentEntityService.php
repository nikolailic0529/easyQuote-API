<?php

namespace App\Services\Attachment;

use App\Enum\AttachmentType;
use App\Events\Attachment\AttachmentCreated;
use App\Events\Attachment\AttachmentDeleted;
use App\Events\Attachment\AttachmentExported;
use App\Models\Attachable;
use App\Models\Attachment;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function tap;

class AttachmentEntityService
{
    public function __construct(protected FilesystemAdapter    $filesystem,
                                protected ConnectionInterface  $connection,
                                protected AttachmentDataMapper $dataMapper,
                                protected Dispatcher           $eventDispatcher)
    {
    }

    public function createAttachmentFromUploadedFile(UploadedFile $file, AttachmentType $attachmentType): Attachment
    {
        $filePath = $this->filesystem->putFileAs(
            path: null,
            file: $file,
            name: $file->hashName(),
        );

        $metadata = $this->filesystem->getMetadata($filePath) + ['filename' => $file->getClientOriginalName()];

        return tap($this->dataMapper->mapFromMetadata($metadata, $attachmentType),
            function (Attachment $attachment): void {
                $this->connection->transaction(static fn() => $attachment->save());
            });
    }

    public function createAttachmentForEntity(UploadedFile   $file,
                                              AttachmentType $attachmentType,
                                              Model          $entity): Attachment
    {
        return tap($this->createAttachmentFromUploadedFile($file, $attachmentType),
            function (Attachment $attachment) use ($entity): void {
                $this->connection->transaction(function () use ($attachment, $entity) {
                    $entity
                        ->morphToMany($attachment::class, 'attachable')
                        ->attach($attachment);

                    $this->touchRelated($attachment);
                });

                $this->eventDispatcher->dispatch(new AttachmentCreated($attachment, $entity));
            });
    }

    public function deleteAttachment(Attachment $attachment, Model $entity): void
    {
        $this->connection->transaction(function () use ($attachment): void {
            $attachment->delete();

            $this->touchRelated($attachment);
        });

        if ($this->filesystem->exists($attachment->filepath)) {
            $this->filesystem->delete($attachment->filepath);
        }

        $this->eventDispatcher->dispatch(new AttachmentDeleted($attachment, $entity));
    }

    public function downloadAttachment(Attachment $attachment): StreamedResponse
    {
        return tap($this->filesystem->download(path: $attachment->filepath, name: $attachment->filename),
            function () use ($attachment): void {
                /** @var Attachable|null $attachable */
                $attachable = Attachable::query()->find($attachment->getKey());

                if (is_null($attachable) || is_null($attachable->related)) {
                    return;
                }

                $this->eventDispatcher->dispatch(new AttachmentExported($attachment, $attachable->related));
            });
    }

    protected function touchRelated(Attachment $attachment): void
    {
        foreach ($attachment->attachables as $attachable) {
            /** @var $attachable Attachable */
            $attachable->related?->touch();
        }
    }

    private static function processFileName(string $filename): string
    {
        $ext = File::extension($filename);

        $maxBasenameLength = 191 - strlen($ext);

        $filename = substr(File::name($filename), 0, $maxBasenameLength);

        return implode('.', [$filename, $ext]);
    }
}
