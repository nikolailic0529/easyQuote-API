<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Models\Address;
use App\Models\Pipeline\Pipeline;
use App\Services\Address\AddressDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

class PushContactStrategy implements PushStrategy
{
    protected ?Pipeline $pipeline = null;

    public function __construct(protected ConnectionInterface         $connection,
                                protected PipelinerContactIntegration $contactIntegration,
                                protected PushClientStrategy          $pushClientStrategy,
                                protected AddressDataMapper           $dataMapper)
    {
    }

    /**
     * @param Address $model
     * @return void
     * @throws \App\Integrations\Pipeliner\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws PipelinerSyncException
     */
    public function sync(Model $model): void
    {
        if (null !== $model->user) {
            $this->pushClientStrategy->sync($model->user);
        }

        if (null === $model->contact) {
            throw PipelinerSyncException::missingAddressToContactRelation($model);
        }

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateContactInput($model);

            $contactEntity = $this->contactIntegration->create($input);

            tap($model, function (Address $address) use ($contactEntity): void {
                $address->pl_reference = $contactEntity->id;

                $this->connection->transaction(static fn() => $address->saveQuietly());
            });

            return;
        }

        $contactEntity = $this->contactIntegration->getById($model->pl_reference);

        $input = $this->dataMapper->mapPipelinerUpdateContactInput($model, $contactEntity);

        if (count($input->getModifiedFields()) > 0) {
            $this->contactIntegration->update($input);
        }
    }

    public function setPipeline(Pipeline $pipeline): static
    {
        return tap($this, fn() => $this->pipeline = $pipeline);
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    public function countPending(): int
    {
        return 0;
    }

    public function iteratePending(): \Traversable
    {
        return Address::query()
            ->lazyById(100);
    }

    public function getModelType(): string
    {
        return (new Address())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Address;
    }
}