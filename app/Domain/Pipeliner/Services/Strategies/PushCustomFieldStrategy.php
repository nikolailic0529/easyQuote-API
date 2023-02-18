<?php

namespace App\Domain\Pipeliner\Services\Strategies;

use App\Domain\CustomField\Models\CustomField;
use App\Domain\CustomField\Models\CustomFieldValue;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerFieldIntegration;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\FieldDataSetItem;
use App\Domain\Pipeliner\Integration\Models\FieldDataSetItemCollection;
use App\Domain\Pipeliner\Integration\Models\FieldEntity;
use App\Domain\Pipeliner\Integration\Models\FieldFilterInput;
use App\Domain\Pipeliner\Integration\Models\UpdateDraftFieldInput;
use App\Domain\Pipeliner\Services\Exceptions\PipelinerSyncException;
use App\Domain\Pipeliner\Services\Strategies\Concerns\SalesUnitsAware;
use App\Domain\Pipeliner\Services\Strategies\Contracts\PushStrategy;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PushCustomFieldStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected Config $config,
                                protected PipelinerFieldIntegration $fieldIntegration)
    {
    }

    /**
     * @param CustomField $model
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof CustomField) {
            throw new \TypeError(sprintf('Model must be an instance of %s.', CustomField::class));
        }

        if (!isset($this->getSyncFieldMap()[$model->field_name])) {
            throw new PipelinerSyncException('Unsupported CustomField model for sync.');
        }

        $this->performSync($model, true);
    }

    private function performSync(CustomField $field, bool $withParentField = false)
    {
        $apiName = $this->getSyncFieldMap()[$field->field_name];

        $plFields = $this->fieldIntegration->getByCriteria(
            FieldFilterInput::new()->entityName(EntityFilterStringField::eq('Opportunity'))
                ->apiName(EntityFilterStringField::eq($apiName))
        );

        /** @var FieldEntity $plField */
        $plField = collect($plFields)->sole();

        $fieldValues = $field->values()->get();

        $plDataSetMap = collect($plField->dataSet)->keyBy('id')->all();

        if ($withParentField && null !== $field->parentField) {
            $this->performSync($field->parentField);
        }

        $fieldValues->each(static function (CustomFieldValue $value) use ($plDataSetMap): void {
            if (null !== $value->pl_reference && false === isset($plDataSetMap[$value->pl_reference])) {
                $value->pl_reference = null;
            }
        });

        $dataSetCollection = $fieldValues->map(function (CustomFieldValue $value, int $n): FieldDataSetItem {
            return new FieldDataSetItem(
                optionName: $value->field_value,
                calcValue: (float) $value->calc_value,
                id: $value->pl_reference,
                allowedBy: $value->allowedBy->pluck('pl_reference')->all(),
            );
        })
            ->pipe(static function (Collection $collection): FieldDataSetItemCollection {
                return new FieldDataSetItemCollection(...$collection->all());
            });

        $input = new UpdateDraftFieldInput(id: $plField->id, dataSet: $dataSetCollection);

        $updatedPlField = $this->fieldIntegration->update($input);

        $fieldValues->each(static function (CustomFieldValue $value, int $n) use ($updatedPlField): void {
            if (isset($updatedPlField->dataSet[$n])) {
                $value->pl_reference = $updatedPlField->dataSet[$n]->id;
            }
        });

        $this->connectionResolver->connection()->transaction(static function () use ($fieldValues): void {
            $fieldValues->each->save();
        });
    }

    private function getSyncFieldMap(): array
    {
        return $this->config->get('pipeliner.sync.custom_fields.mapping', []);
    }

    public function countPending(): int
    {
        return 0;
    }

    public function iteratePending(): \Traversable
    {
        return CustomField::query()
            ->whereIn('field_name', array_keys($this->getSyncFieldMap()))
            ->lazyById(100);
    }

    public function getModelType(): string
    {
        return (new CustomField())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof CustomField;
    }

    public function getByReference(string $reference): object
    {
        return CustomField::query()->findOrFail($reference);
    }
}
