<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerSalesUnitIntegration;
use App\Domain\Pipeliner\Integration\Models\SalesUnitEntity;
use App\Domain\Pipeliner\Services\Exceptions\PipelinerSyncException;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PullStrategy;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\SalesUnit\Services\SalesUnitDataMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

class PullSalesUnitStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected Config $config,
                                protected SalesUnitDataMapper $dataMapper,
                                protected PipelinerSalesUnitIntegration $salesUnitIntegration)
    {
    }

    /**
     * @param SalesUnitEntity $entity
     *
     * @throws PipelinerSyncException
     */
    public function sync(object $entity): Model
    {
        if (!$entity instanceof SalesUnitEntity) {
            throw new \TypeError(sprintf('Entity must be an instance of %s.', SalesUnitEntity::class));
        }

        /** @var SalesUnit|null $model */
        $model = SalesUnit::query()
            ->withTrashed()
            ->where('unit_name', $entity->name)
            ->first();

        $newModel = $this->dataMapper->mapSalesUnitFromSalesUnitEntity($entity);

        if (null !== $model) {
            $this->dataMapper->mergeAttributesFrom($model, $newModel);

            $this->connectionResolver->connection()
                ->transaction(static function () use ($model): void {
                    $model->save();
                });

            return $model;
        }

        $this->connectionResolver->connection()
            ->transaction(static function () use ($newModel): void {
                $newModel->save();
            });

        return $newModel;
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \App\Domain\Pipeliner\Integration\Exceptions\GraphQlRequestException
     * @throws PipelinerSyncException
     */
    public function syncByReference(string $reference): Model
    {
        return $this->sync(
            $this->getByReference($reference)
        );
    }

    public function getByReference(string $reference): SalesUnitEntity
    {
        return $this->salesUnitIntegration->getById($reference);
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class,
        'modified' => \DateTimeInterface::class])]
    public function getMetadata(string $reference): array
    {
        /** @var SalesUnitEntity $plField */
        $entity = $this->getByReference($reference);

        return [
            'id' => $entity->id,
            'revision' => 0,
            'created' => $entity->created,
            'modified' => $entity->modified,
        ];
    }

    public function countPending(): int
    {
        return collect($this->iteratePending())->count();
    }

    public function iteratePending(): \Traversable
    {
        return collect($this->salesUnitIntegration->getAll())
            ->lazy()
            ->map(static function (SalesUnitEntity $entity): array {
                return ['id' => $entity->id, 'modified' => $entity->modified];
            });
    }

    public function getModelType(): string
    {
        return (new SalesUnit())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof SalesUnit;
    }
}
