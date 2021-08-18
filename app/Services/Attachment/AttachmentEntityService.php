<?php

namespace App\Services\Attachment;

use App\Models\Attachment;
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
                                protected ConnectionInterface $connection)
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

        });
    }

    public function deleteAttachment(Attachment $attachment): void
    {
        $this->connection->transaction(function () use ($attachment) {
            $attachment->delete();
        });

        if ($this->filesystem->exists($attachment->filepath)) {
            $this->filesystem->delete($attachment->filepath);
        }
    }

    public function downloadAttachment(Attachment $attachment): StreamedResponse
    {
        return $this->filesystem->download(path: $attachment->filepath, name: $attachment->filename);
    }

    private static function processFileName(string $filename): string
    {
        $ext = File::extension($filename);

        $maxBasenameLength = 191 - strlen($ext);

        $filename = substr(File::name($filename), 0, $maxBasenameLength);

        return implode('.', [$filename, $ext]);
    }
}
