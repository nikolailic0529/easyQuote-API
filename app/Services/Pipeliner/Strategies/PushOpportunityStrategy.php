<?php

namespace App\Services\Pipeliner\Strategies;

use App\Enum\AddressType;
use App\Integrations\Pipeliner\Enum\ValidationLevel;
use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerClientIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerPipelineIntegration;
use App\Integrations\Pipeliner\Models\PipelineEntity;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;
use App\Models\Address;
use App\Models\Opportunity;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeliner\PipelinerSyncStrategyLog;
use App\Models\PipelinerModelUpdateLog;
use App\Services\Opportunity\OpportunityDataMapper;
use App\Services\Pipeliner\Exceptions\PipelinerSyncException;
use App\Services\Pipeliner\PipelinerAccountLookupService;
use App\Services\Pipeliner\PipelinerOpportunityLookupService;
use App\Services\Pipeliner\Strategies\Contracts\PushStrategy;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PushOpportunityStrategy implements PushStrategy
{
    protected ?Pipeline $pipeline = null;
    protected ?PipelineEntity $pipelineEntity = null;

    public function __construct(protected ConnectionInterface               $connection,
                                protected PipelinerAccountLookupService     $accountLookupService,
                                protected PipelinerOpportunityLookupService $opportunityLookupService,
                                protected PipelinerPipelineIntegration      $pipelineIntegration,
                                protected PipelinerAccountIntegration       $accountIntegration,
                                protected PipelinerOpportunityIntegration   $oppIntegration,
                                protected PipelinerClientIntegration        $clientIntegration,
                                protected PushClientStrategy                $pushClientStrategy,
                                protected PushContactStrategy               $pushContactStrategy,
                                protected PushCompanyStrategy               $pushCompanyStrategy,
                                protected PushNoteStrategy                  $pushNoteStrategy,
                                protected PushTaskStrategy                  $pushTaskStrategy,
                                protected PushAppointmentStrategy           $pushAppointmentStrategy,
                                protected OpportunityDataMapper             $dataMapper)
    {
    }


    public function setPipeline(Pipeline $pipeline): static
    {
        return tap($this, function () use ($pipeline): void {
            $this->pipeline = $pipeline;
            $this->pipelineEntity = null;
        });
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    /**
     * @throws PipelinerSyncException
     */
    private function modelsToBeUpdatedQuery(): Builder
    {
        $this->pipeline ?? throw PipelinerSyncException::unsetPipeline();

        $updateLogModel = new PipelinerModelUpdateLog();

        $lastOpportunityUpdatedAt = $updateLogModel->newQuery()
            ->whereBelongsTo($this->pipeline)
            ->where('model_type', $this->getModelType())
            ->latest()
            ->value('latest_model_updated_at');

        $model = new Opportunity();

        $syncStrategyLogModel = new PipelinerSyncStrategyLog();

        return $model->newQuery()
            ->select($model->qualifyColumn('*'))
            ->orderBy($model->getQualifiedUpdatedAtColumn())
            ->whereBelongsTo($this->pipeline)
            ->where(static function (Builder $builder) use ($model): void {
                $builder->whereColumn($model->getQualifiedUpdatedAtColumn(), '>', $model->getQualifiedCreatedAtColumn())
                    ->orWhereNull($model->qualifyColumn('pl_reference'));
            })
            ->leftJoinSub(
                $syncStrategyLogModel->newQuery()
                    ->selectRaw("max({$syncStrategyLogModel->getQualifiedUpdatedAtColumn()}) as {$syncStrategyLogModel->getUpdatedAtColumn()}")
                    ->addSelect($syncStrategyLogModel->model()->getQualifiedForeignKeyName())
                    ->where($syncStrategyLogModel->qualifyColumn('strategy_name'), (string)StrategyNameResolver::from($this))
                    ->groupBy($syncStrategyLogModel->model()->getQualifiedForeignKeyName())
                ,
                'latest_sync_strategy_log',
                "latest_sync_strategy_log.{$syncStrategyLogModel->model()->getForeignKeyName()}",
                $model->getQualifiedKeyName(),
            )
            ->where(static function (Builder $builder) use ($syncStrategyLogModel, $model): void {
                $builder
                    ->whereNull("latest_sync_strategy_log.{$syncStrategyLogModel->getUpdatedAtColumn()}")
                    ->orWhereColumn($model->getQualifiedUpdatedAtColumn(), '>', "latest_sync_strategy_log.{$syncStrategyLogModel->getUpdatedAtColumn()}");
            })
            ->unless(is_null($lastOpportunityUpdatedAt), static function (Builder $builder) use ($model, $lastOpportunityUpdatedAt): void {
                $builder->where($model->getQualifiedUpdatedAtColumn(), '>', $lastOpportunityUpdatedAt);
            });
    }

    /**
     * @throws PipelinerSyncException
     */
    public function countPending(): int
    {
        return $this->modelsToBeUpdatedQuery()->count();
    }

    /**
     * @throws PipelinerSyncException
     */
    public function iteratePending(): \Traversable
    {
        return $this->modelsToBeUpdatedQuery()->lazyById(100);
    }

    private function resolvePipelineEntity(): PipelineEntity
    {
        return $this->pipelineEntity ??= collect($this->pipelineIntegration->getAll())
            ->sole(function (PipelineEntity $entity): bool {
                return 0 === strcasecmp($entity->name, $this->pipeline->pipeline_name);
            });
    }

    /**
     * @param Opportunity $model
     * @return void
     * @throws \App\Integrations\Pipeliner\Exceptions\GraphQlRequestException
     * @throws \App\Services\Pipeliner\Exceptions\MultiplePipelinerEntitiesFoundException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Throwable
     */
    public function sync(Model $model): void
    {
        if (is_null($model->pl_reference)) {
            $oppEntity = $this->opportunityLookupService->find($model, $this->resolvePipelineEntity());

            if (null !== $oppEntity) {
                $model->pl_reference = $oppEntity->id;

                tap($model, function (Opportunity $opportunity): void {
                    $this->connection->transaction(static fn() => $opportunity->saveQuietly());
                });
            }
        }

        // Pushing owner, account, contact entities at first,
        // as we map their ids to the opportunity entity.
        $this->pushOwnerOfOppty($model);
        $this->pushAccountsFromOppty($model);
        $this->pushContactsFromOppty($model);

        if (is_null($model->pl_reference)) {
            $input = $this->dataMapper->mapPipelinerCreateOpportunityInput(
                opportunity: $model,
            );

            $oppEntity = $this->oppIntegration->create($input, ValidationLevelCollection::from(ValidationLevel::SKIP_USER_DEFINED_VALIDATIONS));

            tap($model, function (Opportunity $opportunity) use ($oppEntity): void {
                $opportunity->pl_reference = $oppEntity->id;

                $this->connection->transaction(static fn() => $opportunity->push());
            });
        } else {
            $oppEntity = $this->oppIntegration->getById($model->pl_reference);

            $input = $this->dataMapper->mapPipelinerUpdateOpportunityInput(
                opportunity: $model,
                oppEntity: $oppEntity,
            );

            $modifiedFields = $input->getModifiedFields();

            if (false === empty($modifiedFields)) {
                $this->oppIntegration->update($input, ValidationLevelCollection::from(ValidationLevel::SKIP_USER_DEFINED_VALIDATIONS));
            }
        }

        // Pushing the note, task, appointment entities at last,
        // as they are dependent on the existing opportunity entity.
        $this->pushNotesFromOppty($model);
        $this->pushTasksFromOppty($model);
        $this->pushAppointmentsFromOppty($model);
    }

    public function getModelType(): string
    {
        return (new Opportunity())->getMorphClass();
    }

    private function pushOwnerOfOppty(Opportunity $opportunity): void
    {
        if (null !== $opportunity->owner) {
            $this->pushClientStrategy->sync($opportunity->owner);
        }
    }

    private function pushContactsFromOppty(Opportunity $opportunity): void
    {
        $addressesToBeLinkedWithContact = Collection::make();
        $primaryContact = $opportunity->primaryAccountContact;

        // Check if the primary contact isn't associated with any addresses.
        $primaryContactIsNotAssociatedWithAnyAddress = Collection::wrap($opportunity->primaryAccount?->addresses)
            ->doesntContain(static function (Address $address) use ($primaryContact): bool {
                return $address->contact()->is($primaryContact);
            });

        if ($primaryContactIsNotAssociatedWithAnyAddress && null !== $primaryContact) {
            $primaryAddress = Collection::wrap($opportunity->primaryAccount?->addresses)
                ->sortByDesc('pivot.is_default')
                ->first(static function (Address $address) use ($primaryContact): bool {
                    if ($primaryContact->contact_type === $address->address_type) {
                        $address->contact()->associate($primaryContact);

                        return true;
                    }

                    return false;
                });

            $addressesToBeLinkedWithContact->push(
                $primaryAddress
            );
        }

        $addressesToBeLinkedWithContact = $addressesToBeLinkedWithContact->merge(
            Collection::wrap($opportunity->primaryAccount?->addresses)
                ->filter(static fn(Address $address): bool => null === $address->contact)
                ->each(function (Address $address) use ($opportunity): void {
                    $contact = Collection::wrap($opportunity->primaryAccount?->contacts)
                        ->sortByDesc('pivot.is_default')
                        ->first();

                    $address->contact()->associate($contact);
                })
        );

        $addressesToBeLinkedWithContact = $addressesToBeLinkedWithContact->merge(
            Collection::wrap($opportunity->endUser?->addresses)
                ->filter(static fn(Address $address): bool => null === $address->contact)
                ->each(function (Address $address) use ($opportunity): void {
                    $contact = Collection::wrap($opportunity->endUser?->contacts)
                        ->sortByDesc('pivot.is_default')
                        ->first();

                    $address->contact()->associate($contact);
                })
        );

        $this->connection->transaction(static function () use ($addressesToBeLinkedWithContact): void {
            $addressesToBeLinkedWithContact->each->save();
        });

        Collection::wrap($opportunity->primaryAccount?->addresses)
            ->merge(Collection::wrap($opportunity->endUser?->addresses))
            // Reject the default invoice address from pushing as it's being assumed as a part of account attributes.
            ->reject(static function (Address $address): bool {
                return $address->pivot->is_default && AddressType::INVOICE === $address->address_type;
            })
            ->each(function (Address $address): void {
                $this->pushContactStrategy->sync($address);
            });

        // Refresh the addresses.
        $opportunity->primaryAccount?->load('addresses');
        $opportunity->endUser?->load('addresses');
    }

    private function pushAccountsFromOppty(Opportunity $opportunity): void
    {
        if (null !== $opportunity->primaryAccount) {
            $this->pushCompanyStrategy->sync($opportunity->primaryAccount);
        }

        if (null !== $opportunity->endUser) {
            $this->pushCompanyStrategy->sync($opportunity->endUser);
        }
    }

    public function isApplicableTo(object $entity): bool
    {
        return $entity instanceof Opportunity;
    }

    private function pushNotesFromOppty(Opportunity $opportunity): void
    {
        foreach ($opportunity->notes()->lazyById(100) as $item) {
            $this->pushNoteStrategy->sync($item);
        }
    }

    private function pushTasksFromOppty(Opportunity $opportunity): void
    {
        foreach ($opportunity->tasks()->lazyById(100) as $item) {
            $this->pushTaskStrategy->sync($item);
        }
    }

    private function pushAppointmentsFromOppty(Opportunity $opportunity): void
    {
        foreach ($opportunity->ownAppointments()->lazyById(100) as $item) {
            $this->pushAppointmentStrategy->sync($item);
        }
    }
}