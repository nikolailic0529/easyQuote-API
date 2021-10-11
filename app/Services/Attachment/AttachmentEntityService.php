<?php

namespace App\Services\Attachment;

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
use Webpatser\Uuid\Uuid;
use function tap;

class AttachmentEntityService
{
    public function __construct(protected FilesystemAdapter   $filesystem,
                                protected ConnectionInterface $connection,
                                protected Dispatcher          $eventDispatcher)
    {
    }

    public function createAttachmentFromUploadedFile(UploadedFile $file, string $attachmentType): Attachment
    {
        $filePath = $this->filesystem->putFileAs(
            path: null,
            file: $file,
            name: $file->hashName(),
        );

        $extension = File::extension($file->getClientOriginalName());
        $filename = self::processFileName($file->getClientOriginalName());
        $size = $file->getSize();

        return tap(new Attachment(), function (Attachment $attachment) use ($size, $extension, $filename, $filePath, $attachmentType) {

            $attachment->{$attachment->getKeyName()} = (string)Uuid::generate(4);
            $attachment->filepath = $filePath;
            $attachment->filename = $filename;
            $attachment->extension = $extension;
            $attachment->size = $size;
            $attachment->type = $attachmentType;

            $this->connection->transaction(function () use ($attachment) {
                $attachment->save();
            });

        });
    }

    public function createAttachmentForEntity(UploadedFile $file, string $attachmentType, Model $entity): Attachment
    {
        return tap($this->createAttachmentFromUploadedFile(file: $file, attachmentType: $attachmentType), function (Attachment $attachment) use ($entity) {

            $this->connection->transaction(function () use ($attachment, $entity) {
                $entity
                    ->morphToMany($attachment::class, 'attachable')
                    ->attach($attachment);
            });

            $this->eventDispatcher->dispatch(new AttachmentCreated($attachment, $entity));

        });
    }

    public function deleteAttachment(Attachment $attachment, Model $entity): void
    {
        $this->connection->transaction(function () use ($attachment) {
            $attachment->delete();
        });

        if ($this->filesystem->exists($attachment->filepath)) {
            $this->filesystem->delete($attachment->filepath);
        }

        $this->eventDispatcher->dispatch(new AttachmentDeleted($attachment, $entity));
    }

    public function downloadAttachment(Attachment $attachment): StreamedResponse
    {
        return tap($this->filesystem->download(path: $attachment->filepath, name: $attachment->filename), function () use ($attachment) {
            /** @var Attachable|null $attachable */
            $attachable = Attachable::query()->find($attachment->getKey());

            if (is_null($attachable) || is_null($attachable->related)) {
                return;
            }

            $this->eventDispatcher->dispatch(new AttachmentExported($attachment, $attachable->related));
        });
    }

    private static function processFileName(string $filename): string
    {
        $ext = File::extension($filename);

        $maxBasenameLength = 191 - strlen($ext);

        $filename = substr(File::name($filename), 0, $maxBasenameLength);

        return implode('.', [$filename, $ext]);
    }
}
