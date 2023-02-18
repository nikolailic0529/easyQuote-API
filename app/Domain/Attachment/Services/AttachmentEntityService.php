<?php

namespace App\Domain\Attachment\Services;

use App\Domain\Attachment\DataTransferObjects\CreateAttachmentData;
use App\Domain\Attachment\Events\AttachmentCreated;
use App\Domain\Attachment\Events\AttachmentDeleted;
use App\Domain\Attachment\Events\AttachmentExported;
use App\Domain\Attachment\Models\Attachable;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\User\Models\User;
use App\Foundation\Filesystem\BinaryFileContent;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly FilesystemAdapter $filesystem,
        protected readonly ConnectionInterface $connection,
        protected readonly AttachmentDataMapper $dataMapper,
        protected readonly Dispatcher $eventDispatcher
    ) {
    }

    public function createAttachmentFromFile(CreateAttachmentData $data): Attachment
    {
        $metadata = $this->storeAttachmentFile($data->file);

        return \tap($this->dataMapper->mapFromMetadata($metadata, $data->type, $data->isDeleteProtected),
            function (Attachment $attachment): void {
                if ($this->causer instanceof User) {
                    $attachment->owner()->associate($this->causer);
                }

                $this->connection->transaction(static fn () => $attachment->save());
            });
    }

    public function createAttachmentForEntity(CreateAttachmentData $data, Model $entity): Attachment
    {
        $metadata = $this->storeAttachmentFile($data->file);

        return \tap($this->dataMapper->mapFromMetadata($metadata, $data->type, $data->isDeleteProtected),
            function (Attachment $attachment) use ($entity): void {
                if ($this->causer instanceof User) {
                    $attachment->owner()->associate($this->causer);
                }

                $this->connection->transaction(function () use ($attachment, $entity) {
                    $attachment->save();

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
        return \tap($this->filesystem->download(path: $attachment->filepath, name: $attachment->filename),
            function () use ($attachment): void {
                /** @var \App\Domain\Attachment\Models\Attachable|null $attachable */
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
            /* @var $attachable \App\Domain\Attachment\Models\Attachable */
            $attachable->related?->touch();
        }
    }

    protected function storeAttachmentFile(UploadedFile|BinaryFileContent $file): array
    {
        if (is_a($file, UploadedFile::class, true)) {
            return $this->storeUploadedFile($file);
        }

        if (is_a($file, BinaryFileContent::class, true)) {
            return $this->storeBinaryFile($file);
        }

        throw new \InvalidArgumentException('Unsupported attachment file type.');
    }

    protected function storeBinaryFile(BinaryFileContent $file): array
    {
        $this->filesystem->put(
            $filePath = Str::random(40),
            $file->content,
        );

        return $this->filesystem->getMetadata($filePath) + ['filename' => $file->filename];
    }

    protected function storeUploadedFile(UploadedFile $file): array
    {
        $filePath = $this->filesystem->putFileAs(
            path: null,
            file: $file,
            name: $file->hashName(),
        );

        return $this->filesystem->getMetadata($filePath) + ['filename' => $file->getClientOriginalName()];
    }

    private static function processFileName(string $filename): string
    {
        $ext = File::extension($filename);

        $maxBasenameLength = 191 - strlen($ext);

        $filename = substr(File::name($filename), 0, $maxBasenameLength);

        return implode('.', [$filename, $ext]);
    }

    public function setCauser(?Model $causer): static
    {
        return \tap($this, fn () => $this->causer = $causer);
    }
}
