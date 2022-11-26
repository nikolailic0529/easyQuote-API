<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\CloudObjectIntegration;
use App\Integrations\Pipeliner\Models\CloudObjectEntity;
use App\Models\Attachment;
use App\Services\Attachment\AttachmentDataMapper;
use App\Services\Attachment\AttachmentFileService;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\LazyCollection;
use JetBrains\PhpStorm\ArrayShape;

class PullAttachmentStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected CloudObjectIntegration      $cloudObjectIntegration,
                                protected AttachmentDataMapper        $dataMapper,
                                protected AttachmentFileService       $fileService)
    {
    }

    /**
     * @param CloudObjectEntity $entity
     * @return Attachment
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \Throwable
     */
    public function sync(object $entity): Model
    {
        if (!$entity instanceof CloudObjectEntity) {
            throw new \TypeError(sprintf("Entity must be an instance of %s.", CloudObjectEntity::class));
        }

        /** @var Attachment|null $attachment */
        $attachment = Attachment::query()
            ->where('pl_reference', $entity->id)
            ->withTrashed()
            ->first();

        // update if already exists
        if (null !== $attachment) {
            $this->dataMapper->mergeAttributesFromCloudObjectEntity($attachment, $entity);

            $this->connectionResolver->connection()->transaction(static fn () => $attachment->save());

            return $attachment;
        }

        $metadata = $this->fileService->downloadFromUrl($entity->url);

        return tap($this->dataMapper->mapFromCloudObjectEntity($entity, $metadata), function (Attachment $attachment): void {
            $this->connectionResolver->connection()->transaction(static fn() => $attachment->save());
        });
    }

    public function batch(object $entity, object ...$entities): array
    {
        $entities = collect([$entity, ...$entities])->lazy();

        foreach ($entities as $entity) {
            if (!$entity instanceof CloudObjectEntity) {
                throw new \TypeError(sprintf("Entity must be an instance of %s.", CloudObjectEntity::class));
            }
        }

        $entitiesMap = $entities->keyBy('id')->collect();

        /** @var Collection $map */
        $map = Attachment::query()
            ->whereIn('pl_reference', $entities->pluck('id')->all())
            ->withTrashed()
            ->get()
            ->keyBy(static function (Attachment $attachment): string {
                return $attachment->pl_reference;
            });

        $pending = $entities
            ->filter(static function (CloudObjectEntity $entity) use ($map): bool {
                return !$map->has($entity->id);
            })
            ->keyBy(static function (CloudObjectEntity $entity): string {
                return $entity->url;
            })
            ->pipe(static function (LazyCollection $collection): BaseCollection {
                return collect($collection->all());
            });

        $map->each(function (Attachment $attachment) use ($entitiesMap) {
            /** @var CloudObjectEntity|null $entity */
            $entity = $entitiesMap[$attachment->pl_reference] ?? null;

            if (null === $entity) {
                return;
            }

            $this->dataMapper->mergeAttributesFromCloudObjectEntity($attachment, $entity);
        });

        $this->connectionResolver->connection()
            ->transaction(static function () use ($map): void {
                $map->each->save();
            });

        if ($pending->isEmpty()) {
            return $map->values()->all();
        }

        $attachments = collect($this->fileService->downloadMultipleFromUrls(...$pending->pluck('url')->all()))
            ->map(function (array $metadata, string $url) use ($pending): Attachment {
                return tap($this->dataMapper->mapFromCloudObjectEntity($pending[$url], $metadata),
                    function (Attachment $attachment): void {
                        $this->connectionResolver->connection()->transaction(static fn() => $attachment->save());
                    });
            })
            ->values();

        return $map->values()
            ->merge($attachments)
            ->all();
    }

    public function syncByReference(string $reference): Model
    {
        throw new PipelinerSyncException("Unsupported sync by reference.");
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class,
        'modified' => \DateTimeInterface::class])]
    public function getMetadata(string $reference): array
    {
        return [
            'id' => $reference,
            'revision' => 0,
            'created' => null,
            'modified' => null,
        ];
    }

    public function countPending(): int
    {
        return 0;
    }

    public function iteratePending(): \Traversable
    {
        return collect();
    }

    public function getModelType(): string
    {
        return (new Attachment())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Attachment;
    }

    public function getByReference(string $reference): object
    {
        throw new PipelinerSyncException("Unsupported get by reference.");
    }
}