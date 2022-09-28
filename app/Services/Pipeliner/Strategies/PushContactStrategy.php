<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\Enum\ValidationLevel;
use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use App\Models\Contact;
use App\Services\Contact\ContactDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PushContactStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionInterface         $connection,
                                protected PipelinerContactIntegration $contactIntegration,
                                protected PushSalesUnitStrategy       $pushSalesUnitStrategy,
                                protected PushClientStrategy          $pushClientStrategy,
                                protected ContactDataMapper           $dataMapper)
    {
    }

    /**
     * @param Contact $model
     * @return void
     * @throws \App\Integrations\Pipeliner\Exceptions\GraphQlRequestException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws PipelinerSyncException
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof Contact) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", Contact::class));
        }

        if (null !== $model->user) {
            $this->pushClientStrategy->sync($model->user);
        }

        if (null !== $model->salesUnit) {
            $this->pushSalesUnitStrategy->sync($model->salesUnit);
        }

        if (null === $model->address) {
            throw PipelinerSyncException::undefinedContactAddressRelation($model);
        }

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateContactInput($model);

            $contactEntity = $this->contactIntegration->create($input, ValidationLevelCollection::from(ValidationLevel::SKIP_ALL));

            tap($model, function (Contact $contact) use ($contactEntity): void {
                $contact->pl_reference = $contactEntity->id;

                $this->connection->transaction(static fn() => $contact->saveQuietly());
            });

            return;
        }

        $contactEntity = $this->contactIntegration->getById($model->pl_reference);

        $input = $this->dataMapper->mapPipelinerUpdateContactInput($model, $contactEntity);

        if (count($input->getModifiedFields()) > 0) {
            $this->contactIntegration->update($input, ValidationLevelCollection::from(ValidationLevel::SKIP_ALL));
        }
    }

    public function countPending(): int
    {
        return 0;
    }

    public function iteratePending(): \Traversable
    {
        $model = new Contact();

        return $model->newQuery()
            ->whereIn($model->salesUnit()->getQualifiedForeignKeyName(), Collection::make($this->getSalesUnits())->modelKeys())
            ->lazyById(100);
    }

    public function getModelType(): string
    {
        return (new Contact())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Contact;
    }

    public function getByReference(string $reference): object
    {
        return Contact::query()->findOrFail($reference);
    }
}