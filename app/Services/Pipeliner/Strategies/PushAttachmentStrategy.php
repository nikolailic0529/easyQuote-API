<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\CloudObjectIntegration;
use App\Models\Attachment;
use App\Services\Attachment\AttachmentDataMapper;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class PushAttachmentStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected AttachmentDataMapper        $dataMapper,
                                protected CloudObjectIntegration      $cloudObjectIntegration)
    {
    }

    /**
     * @param Attachment $model
     * @return void
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof Attachment) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", Attachment::class));
        }

        if (null !== $model->pl_reference) {
            return;
        }

        $input = $this->dataMapper->mapPipelinerCreateCloudObjectInput($model);

        $entity = $this->cloudObjectIntegration->create($input);

        tap($model, function (Attachment $attachment) use ($entity): void {
            $attachment->pl_reference = $entity->id;

            $this->connectionResolver->connection()->transaction(static fn() => $attachment->save());
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
}