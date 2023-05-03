<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Attachment\Models\Attachment;
use App\Domain\Attachment\Services\AttachmentDataMapper;
use App\Domain\Pipeliner\Integration\GraphQl\CloudObjectIntegration;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PushStrategy;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class PushAttachmentStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(
        protected ConnectionResolverInterface $connectionResolver,
        protected AttachmentDataMapper $dataMapper,
        protected CloudObjectIntegration $cloudObjectIntegration,
        protected PushClientStrategy $pushClientStrategy,
    ) {
    }

    /**
     * @param \App\Domain\Attachment\Models\Attachment $model
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof Attachment) {
            throw new \TypeError(sprintf('Model must be an instance of %s.', Attachment::class));
        }

        if ($model->pl_reference !== null) {
            return;
        }

        if ($model->owner !== null) {
            $this->pushClientStrategy->sync($model->owner);
        }

        $input = $this->dataMapper->mapPipelinerCreateCloudObjectInput($model);

        $entity = $this->cloudObjectIntegration->create($input);

        tap($model, function (Attachment $attachment) use ($entity): void {
            $attachment->pl_reference = $entity->id;

            $this->connectionResolver->connection()->transaction(static fn () => $attachment->save());
        });
    }

    public function countPending(): int
    {
        return 0;
    }

    public function iteratePending(): \Traversable
    {
        return Attachment::query()
            ->lazyById(100);
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
        return Attachment::query()->findOrFail($reference);
    }
}
