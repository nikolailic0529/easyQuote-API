<?php

namespace App\Domain\Attachment\Services;

use App\Domain\Attachment\Contracts\AttachmentHasher;
use App\Domain\Attachment\Enum\AttachmentType;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Pipeliner\Integration\Enum\CloudObjectTypeEnum;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Models\CloudObjectEntity;
use App\Domain\Pipeliner\Integration\Models\CreateCloudObjectInput;
use App\Domain\Pipeliner\Services\PipelinerClientEntityToUserProjector;
use App\Domain\User\Services\ApplicationUserResolver;
use Illuminate\Filesystem\FilesystemAdapter;
use Webpatser\Uuid\Uuid;

class AttachmentDataMapper
{
    public function __construct(
        protected readonly FilesystemAdapter $filesystem,
        protected readonly AttachmentHasher $hasher,
        protected readonly PipelinerClientEntityToUserProjector $clientProjector,
        protected readonly ApplicationUserResolver $applicationUserResolver,
    ) {
    }

    public function mapFromCloudObjectEntity(CloudObjectEntity $entity, array $metadata): Attachment
    {
        return tap($this->mapFromMetadata($metadata, AttachmentType::PipelinerDocument),
            function (Attachment $attachment) use ($entity) {
                $this->mergeAttributesFromCloudObjectEntity($attachment, $entity);
            });
    }

    public function mergeAttributesFromCloudObjectEntity(Attachment $attachment, CloudObjectEntity $entity): void
    {
        $attachment->pl_reference = $entity->id;
        $attachment->filename = static::truncateFilename($entity->filename);
        $attachment->extension = pathinfo($entity->filename, PATHINFO_EXTENSION);

        if ($entity->creator !== null) {
            $attachment->owner()->associate(($this->clientProjector)($entity->creator));
        }
    }

    public function mapPipelinerCreateCloudObjectInput(Attachment $attachment): CreateCloudObjectInput
    {
        $stream = $this->filesystem->readStream($attachment->filepath);

        stream_filter_append($stream, 'convert.base64-encode', STREAM_FILTER_READ);

        $content = stream_get_contents($stream);

        $creatorId = $attachment->owner?->pl_reference ?? InputValueEnum::Miss;

        return new CreateCloudObjectInput(
            filename: $attachment->filename,
            type: CloudObjectTypeEnum::S3File,
            content: $content,
            creatorId: $creatorId,
        );
    }

    public function mapFromMetadata(array $metadata, AttachmentType $type, bool $isDeleteProtected = false): Attachment
    {
        return tap(new Attachment(), function (Attachment $attachment) use ($isDeleteProtected, $metadata, $type) {
            $attachment->{$attachment->getKeyName()} = (string) Uuid::generate(4);
            $attachment->filepath = $metadata['path'];
            $attachment->filename = static::truncateFilename($metadata['filename'] ?? pathinfo($metadata['path'],
                PATHINFO_FILENAME));
            $attachment->extension = pathinfo($metadata['path'], PATHINFO_EXTENSION);
            $attachment->size = $metadata['size'];
            $attachment->type = $type;
            $attachment->md5_hash = $this->hasher->hash($this->filesystem->path($metadata['path']));
            if ($isDeleteProtected) {
                $attachment->flags |= Attachment::IS_DELETE_PROTECTED;
            }
            $attachment->updateTimestamps();
        });
    }

    public static function truncateFilename(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $filename = mb_substr(pathinfo($filename, PATHINFO_FILENAME), 0, 191 - mb_strlen($ext));

        return implode('.', array_filter([$filename, $ext], 'filled'));
    }

    public function cloneAttachment(Attachment $attachment): Attachment
    {
        return (new Attachment())->setRawAttributes($attachment->getRawOriginal());
    }
}
