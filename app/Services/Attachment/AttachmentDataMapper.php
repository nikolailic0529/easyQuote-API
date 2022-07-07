<?php

namespace App\Services\Attachment;

use App\Enum\AttachmentType;
use App\Integrations\Pipeliner\Enum\CloudObjectTypeEnum;
use App\Integrations\Pipeliner\Models\CloudObjectEntity;
use App\Integrations\Pipeliner\Models\CreateCloudObjectInput;
use App\Models\Attachment;
use App\Models\Company;
use App\Services\ThumbHelper;
use Illuminate\Filesystem\FilesystemAdapter;
use Webpatser\Uuid\Uuid;

class AttachmentDataMapper
{
    public function __construct(protected FilesystemAdapter $filesystem)
    {
    }

    public function mapFromCloudObjectEntity(CloudObjectEntity $entity, array $metadata): Attachment
    {
        return tap($this->mapFromMetadata($metadata, AttachmentType::PipelinerDocument), function (Attachment $attachment) use ($entity) {
            $attachment->pl_reference = $entity->id;
            $attachment->filename = static::truncateFilename($entity->filename);
            $attachment->extension = pathinfo($entity->filename, PATHINFO_EXTENSION);
        });
    }

    public function mapPipelinerCreateCloudObjectInput(Attachment $attachment): CreateCloudObjectInput
    {
        $stream = $this->filesystem->readStream($attachment->filepath);

        stream_filter_append($stream,  'convert.base64-encode', STREAM_FILTER_READ);

        $content = stream_get_contents($stream);

        return new CreateCloudObjectInput(
            filename: $attachment->filename,
            type: CloudObjectTypeEnum::S3File,
            content: $content
        );
    }

    public function mapFromMetadata(array $metadata, AttachmentType $type): Attachment
    {
        return tap(new Attachment(), function (Attachment $attachment) use ($metadata, $type) {
            $attachment->{$attachment->getKeyName()} = (string)Uuid::generate(4);
            $attachment->filepath = $metadata['path'];
            $attachment->filename = static::truncateFilename($metadata['filename'] ?? pathinfo($metadata['path'], PATHINFO_FILENAME));
            $attachment->extension = pathinfo($metadata['path'], PATHINFO_EXTENSION);
            $attachment->size = $metadata['size'];
            $attachment->type = $type;
            $attachment->updateTimestamps();
        });
    }

    public static function truncateFilename(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $filename = mb_substr(pathinfo($filename, PATHINFO_FILENAME), 0, 191 - mb_strlen($ext));

        return implode('.', array_filter([$filename, $ext], 'filled'));
    }
}