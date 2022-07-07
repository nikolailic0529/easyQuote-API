<?php

namespace App\Services\Pipeliner\Strategies;

use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Models\Company;
use App\Models\Pipeline\Pipeline;
use App\Models\PipelinerModelUpdateLog;
use App\Services\Company\CompanyDataMapper;
use App\Services\Pipeliner\PipelinerAccountLookupService;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PushCompanyStrategy implements PushStrategy
{
    protected ?Pipeline $pipeline = null;

    public function __construct(protected ConnectionInterface           $connection,
                                protected PipelinerAccountLookupService $accountLookupService,
                                protected PipelinerAccountIntegration   $accountIntegration,
                                protected CompanyDataMapper             $dataMapper,
                                protected PushClientStrategy            $pushClientStrategy)
    {
    }

    private function modelsToBeUpdatedQuery(): Builder
    {
        $lastUpdatedAt = PipelinerModelUpdateLog::query()
            ->where('model_type', $this->getModelType())
            ->latest()
            ->value('latest_model_updated_at');

        $model = new Company();

        return $model->newQuery()
            ->orderBy($model->getQualifiedUpdatedAtColumn())
            ->where(static function (Builder $builder) use ($model): void {
                $builder->whereColumn($model->getQualifiedUpdatedAtColumn(), '>', $model->getQualifiedCreatedAtColumn())
                    ->orWhereNull($model->qualifyColumn('pl_reference'));
            })
            ->unless(is_null($lastUpdatedAt), static function (Builder $builder) use ($model, $lastUpdatedAt): void {
                $builder->where($model->getQualifiedUpdatedAtColumn(), '>', $lastUpdatedAt);
            });
    }

    /**
     * @param Company $model
     * @return void
     */
    public function sync(Model $model): void
    {
        if (null !== $model->owner) {
            $this->pushClientStrategy->sync($model->owner);
        }

        if (null === $model->pl_reference) {
            $accountEntity = $this->accountLookupService->find($model);

            if (null !== $accountEntity) {
                tap($model, function (Company $company) use ($accountEntity): void {
                    $company->pl_reference = $accountEntity->id;

                    $this->connection->transaction(static fn() => $company->saveQuietly());
                });
            }
        }

        if (null === $model->pl_reference) {
            $input = $this->dataMapper->mapPipelinerCreateAccountInput($model);

            $accountEntity = $this->accountIntegration->create($input);

            tap($model, function (Company $company) use ($accountEntity): void {
                $company->pl_reference = $accountEntity->id;

                $this->connection->transaction(static fn() => $company->saveQuietly());
            });
        } else {
            $accountEntity = $this->accountIntegration->getById($model->pl_reference);

            $input = $this->dataMapper->mapPipelinerUpdateAccountInput($model, $accountEntity);

            $modifiedFields = $input->getModifiedFields();

            if (false === empty($modifiedFields)) {
                $this->accountIntegration->update($input);
            }
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
        return $this->modelsToBeUpdatedQuery()->count();
    }

    public function iteratePending(): \Traversable
    {
        return $this->modelsToBeUpdatedQuery()
            ->lazyById(100);
    }

    public function getModelType(): string
    {
        return (new Company())->getMorphClass();
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Company;
    }
}