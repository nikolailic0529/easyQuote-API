<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerFieldIntegration;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\FieldDataSetItem;
use App\Integrations\Pipeliner\Models\FieldDataSetItemCollection;
use App\Integrations\Pipeliner\Models\FieldEntity;
use App\Integrations\Pipeliner\Models\FieldFilterInput;
use App\Integrations\Pipeliner\Models\UpdateDraftFieldInput;
use App\Models\Pipeline\Pipeline;
use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PushCustomFieldStrategy implements PushStrategy
{
    protected ?Pipeline $pipeline = null;

    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected Config                      $config,
                                protected PipelinerFieldIntegration   $fieldIntegration)
    {
    }

    /**
     * @param CustomField $model
     * @return void
     */
    public function sync(Model $model): void
    {
        if (!isset($this->getSyncFieldMap()[$model->field_name])) {
            throw new PipelinerSyncException("Unsupported CustomField model for sync.");
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

        $dataSetCollection = $fieldValues->map(function (CustomFieldValue $value, int $n) use ($plField): FieldDataSetItem {
            return new FieldDataSetItem(
                optionName: $value->field_value,
                calcValue: $n,
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
}