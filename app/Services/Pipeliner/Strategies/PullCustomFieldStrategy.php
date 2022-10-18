<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerFieldIntegration;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\FieldEntity;
use App\Integrations\Pipeliner\Models\FieldFilterInput;
use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use App\Services\CustomField\CustomFieldDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PullStrategy;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use JetBrains\PhpStorm\ArrayShape;

class PullCustomFieldStrategy implements PullStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected Config                      $config,
                                protected CustomFieldDataMapper       $dataMapper,
                                protected PipelinerFieldIntegration   $fieldIntegration)
    {
    }

    /**
     * @param FieldEntity $entity
     * @return Model
     * @throws PipelinerSyncException
     */
    public function sync(object $entity): Model
    {
        if (!$entity instanceof FieldEntity) {
            throw new \TypeError(sprintf("Entity must be an instance of %s.", FieldEntity::class));
        }

        $this->ensureFieldReferenceSupportedForSync($entity->apiName);

        $fieldName = $this->resolveLocalFieldName($entity->apiName);

        /** @var CustomField|null $model */
        $model = CustomField::query()
            ->withTrashed()
            ->where('field_name', $fieldName)
            ->first();

        if (null !== $model) {
            $updatedModel = $this->dataMapper->mapCustomFieldFromFieldEntity($entity, $fieldName);

            $this->dataMapper->mergeAttributesFrom($model, $updatedModel);

            $this->connectionResolver->connection()->transaction(static function () use ($model): void {
                $model->save();
                $model->values->each(static function (CustomFieldValue $value) use ($model) {
                    $value->customField()->associate($model);
                    $value->save();
                    $value->allowedBy()->sync($value->allowedBy);
                });
            });

            return $model;
        }

        $model = $this->dataMapper->mapCustomFieldFromFieldEntity($entity, $fieldName);

        $this->connectionResolver->connection()->transaction(static function () use ($model): void {
            $model->save();
            $model->values->each(static function (CustomFieldValue $value) use ($model): void {
                $value->customField()->associate($model);
                $value->save();
                $value->allowedBy()->sync($value->allowedBy);
            });
        });

        return $model;
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \App\Integrations\Pipeliner\Exceptions\GraphQlRequestException
     * @throws PipelinerSyncException
     */
    public function syncByReference(string $reference): Model
    {
        $this->ensureFieldReferenceSupportedForSync($reference);

        return $this->sync($this->getByReference($reference));
    }

    public function getByReference(string $reference): FieldEntity
    {
        $plFields = $this->fieldIntegration->getByCriteria(
            FieldFilterInput::new()->entityName(EntityFilterStringField::eq('Opportunity'))
                ->apiName(EntityFilterStringField::eq($reference))
        );

        /** @var FieldEntity $plField */
        return collect($plFields)->sole();
    }

    /**
     * @throws PipelinerSyncException
     */
    private function ensureFieldReferenceSupportedForSync(string $reference): void
    {
        if (collect($this->getSyncFieldMap())->doesntContain($reference)) {
            throw new PipelinerSyncException("Unsupported field reference for sync.");
        }
    }

    private function resolveLocalFieldName(string $reference): string
    {
        return collect($this->getSyncFieldMap())->search($reference) ?: '';
    }

    private function getSyncFieldMap(): array
    {
        return $this->config->get('pipeliner.sync.custom_fields.mapping', []);
    }

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class,
        'modified' => \DateTimeInterface::class])]
    public function getMetadata(string $reference): array
    {
        /** @var FieldEntity $plField */
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
        $apiNameFilters = collect($this->getSyncFieldMap())->map(static function (string $apiName): FieldFilterInput {
            return FieldFilterInput::new()->apiName(EntityFilterStringField::eq($apiName));
        })->all();

        return LazyCollection::make($this->fieldIntegration->getByCriteria(
            FieldFilterInput::new()
                ->entityName(EntityFilterStringField::eq('Opportunity'))
                ->OR(...$apiNameFilters)
        ))
            ->sortBy(function (FieldEntity $entity): int {
                return array_search($entity->apiName, array_values($this->getSyncFieldMap()), true) ?: 0;
            })
            ->map(static function (FieldEntity $entity): array {
                return ['id' => $entity->apiName, 'modified' => $entity->modified];
            });
    }

    public function getModelType(): string
    {
        return (new CustomField())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof CustomField;
    }
}