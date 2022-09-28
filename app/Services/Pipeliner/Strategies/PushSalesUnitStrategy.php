<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerSalesUnitIntegration;
use App\Integrations\Pipeliner\Models\CreateSalesUnitInput;
use App\Models\SalesUnit;
use App\Services\Pipeliner\PipelinerSalesUnitLookupService;
use App\Services\Pipeliner\Strategies\Concerns\SalesUnitsAware;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class PushSalesUnitStrategy implements PushStrategy
{
    use SalesUnitsAware;

    public function __construct(protected ConnectionResolverInterface     $connectionResolver,
                                protected PipelinerSalesUnitLookupService $unitLookupService,
                                protected PipelinerSalesUnitIntegration   $unitIntegration)
    {
    }

    /**
     * @param SalesUnit $model
     * @return void
     * @throw \TypeError
     */
    public function sync(Model $model): void
    {
        if (!$model instanceof SalesUnit) {
            throw new \TypeError(sprintf("Model must be an instance of %s.", SalesUnit::class));
        }

        if (null !== $model->pl_reference) {
            return;
        }

        $entity = $this->unitLookupService->find($model);

        if (null === $entity) {
            $entity = $this->unitIntegration->create(new CreateSalesUnitInput(name: $model->unit_name));
        }

        tap($model, function (SalesUnit $model) use ($entity): void {
            $model->pl_reference = $entity->id;

            $this->connectionResolver->connection()->transaction(static fn() => $model->save());
        });
    }

    public function countPending(): int
    {
        return SalesUnit::query()
            ->whereNull('pl_reference')
            ->count();
    }

    public function iteratePending(): \Traversable
    {
        return SalesUnit::query()
            ->whereNull('pl_reference')
            ->lazyById();
    }

    public function getModelType(): string
    {
        return (new SalesUnit())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof SalesUnit;
    }

    public function getByReference(string $reference): object
    {
        return SalesUnit::query()->findOrFail($reference);
    }
}