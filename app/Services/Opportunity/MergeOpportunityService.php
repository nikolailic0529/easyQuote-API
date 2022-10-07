<?php

namespace App\Services\Opportunity;

use App\Contracts\LoggerAware;
use App\Models\Appointment\Appointment;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Quote\WorldwideQuote;
use App\Models\Task\Task;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MergeOpportunityService implements LoggerAware
{
    public function __construct(
        protected readonly ConnectionResolverInterface $connectionResolver,
        protected readonly LockProvider $lockProvider,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function work(): void
    {
        $model = new Opportunity();

        $query = $model->newQuery();

        /** @var \Staudenmeir\LaravelCte\Query\Builder $query */
        $query->withExpression('calculated_opportunities',
            $model->newQuery()
                ->addSelect([
                    $model->getQualifiedKeyName(),
                    DB::raw('count(*) over (partition by project_name) as `quantity`'),
                    DB::raw('row_number() over (partition by project_name order by updated_at desc) as `number`'),
                ])
                ->toBase(),
        );

        $opportunitiesToBeMerged = $query
            ->join('calculated_opportunities', "calculated_opportunities.{$model->getKeyName()}",
                $model->getQualifiedKeyName())
            ->where('calculated_opportunities.quantity', '>', 1)
            ->where('calculated_opportunities.number', 1)
            ->lazyById(column: $model->getQualifiedKeyName());

        $opportunitiesToBeMerged
            ->each(function (Opportunity $opportunity) {
                $other = Opportunity::query()
                    ->whereKeyNot($opportunity->getKey())
                    ->where('project_name', $opportunity->project_name)
                    ->get();

                $this->mergeOpportunitiesTo(
                    opportunity: $opportunity,
                    other: $other
                );
            });

    }

    public function mergeOpportunitiesTo(Opportunity $opportunity, Collection $other): void
    {
        $this->logger->info("Opportunity merge: starting...", [
            'opportunity' => $opportunity->only([$opportunity->getKeyName(), 'project_name']),
            'other' => $other->modelKeys(),
        ]);

        $sortedOther = $other->sortByDesc($opportunity->getUpdatedAtColumn());
        $sortedOther->load([
            'primaryAccount',
            'endUser',
            'primaryAccountContact',
            'recurrence',
            'tasks',
            'ownAppointments',
            'notes',
            'opportunitySuppliers',
            'worldwideQuotes',
        ]);

        $this->connectionResolver->connection()
            ->transaction(function () use ($opportunity, $sortedOther): void {
                $sortedOther->each(function (Opportunity $another) use ($opportunity) {
                    $this->performMerge($opportunity, $another);

                    $another->delete();
                });
            });

        $this->logger->info("Opportunity merge: success.", [
            'opportunity' => $opportunity->only([$opportunity->getKeyName(), 'project_name']),
        ]);
    }

    protected function performMerge(Opportunity $opportunity, Opportunity $another): void
    {
        $emptyAttributes = collect($opportunity->attributesToArray())
            ->lazy()
            ->reject(static function (mixed $value, string $attribute): bool {
                return str_ends_with($attribute, '_id');
            })
            ->reject(static function (mixed $value, string $attribute) use ($opportunity): bool {
                return in_array($attribute, [
                    $opportunity->getCreatedAtColumn(),
                    $opportunity->getUpdatedAtColumn(),
                    $opportunity->getDeletedAtColumn(),
                ], true);
            })
            ->filter(static function (mixed $value): bool {
                return blank($value);
            })
            ->all();

        $newAttributes = collect($another->attributesToArray())
            ->only(array_keys($emptyAttributes))
            ->filter(static function (mixed $value): bool {
                return filled($value);
            })
            ->all();

        $opportunity->forceFill($newAttributes);

        if (null === $opportunity->primaryAccount) {
            $opportunity->primaryAccount()->associate($another->primaryAccount);
        }

        if (null === $opportunity->endUser) {
            $opportunity->endUser()->associate($another->endUser);
        }

        if (null === $opportunity->primaryAccountContact) {
            $opportunity->primaryAccountContact()->associate($another->primaryAccountContact);
        }

        $opportunity->saveQuietly();

        if (null === $opportunity->recurrence && null !== $another->recurrence) {
            $anotherRecurrence = $another->recurrence;

            $recurrence = tap($another->recurrence->replicate(),
                static function (Opportunity\OpportunityRecurrence $recurrence) use (
                    $opportunity,
                    $anotherRecurrence
                ): void {
                    $recurrence->opportunity()->associate($opportunity);
                    $recurrence->setCreatedAt($anotherRecurrence->{$anotherRecurrence->getCreatedAtColumn()});
                    $recurrence->setUpdatedAt($anotherRecurrence->{$anotherRecurrence->getUpdatedAtColumn()});
                });

            $recurrence->saveQuietly();

            $opportunity->setRelation('recurrence', $recurrence);
        }

        $tasks = $another->tasks->map(static function (Task $anotherTask): Task {
            return tap(static::replicateModelWithTimestamps($anotherTask))->saveQuietly();
        });

        $opportunity->tasks()->attach($tasks);

        $ownAppointments = $another->ownAppointments->map(static function (Appointment $anotherAppointment
        ): Task {
            return tap(static::replicateModelWithTimestamps($anotherAppointment))->saveQuietly();
        });

        $opportunity->ownAppointments()->attach($ownAppointments);

        $notes = $another->loadMissing('notes')->getRelation('notes')->map(static function (
            Note $anotherNote
        ): Task {
            return tap(static::replicateModelWithTimestamps($anotherNote))->saveQuietly();
        });

        $opportunity->notes()->attach($notes);

        $opportunitySuppliers = $another->opportunitySuppliers
            ->reject(static function (OpportunitySupplier $anotherSupplier) use ($opportunity): bool {
                return $opportunity
                    ->opportunitySuppliers
                    ->lazy()
                    ->filter(static function (OpportunitySupplier $supplier) use ($anotherSupplier): bool {
                        return mb_strtolower($supplier->supplier_name) === mb_strtolower($anotherSupplier->supplier_name);
                    })
                    ->filter(static function (OpportunitySupplier $supplier) use ($anotherSupplier): bool {
                        return mb_strtolower($supplier->country_name) === mb_strtolower($anotherSupplier->country_name);
                    })
                    ->isNotEmpty();
            })
            ->map(
                static function (OpportunitySupplier $anotherSupplier) use ($opportunity
                ): OpportunitySupplier {
                    return tap(static::replicateModelWithTimestamps($anotherSupplier),
                        static function (OpportunitySupplier $supplier) use (
                            $anotherSupplier,
                            $opportunity
                        ): void {
                            $supplier->opportunity()->associate($opportunity);
                            $supplier->saveQuietly();
                        });
                }
            )
            ->values();

        $opportunity->opportunitySuppliers->merge($opportunitySuppliers);

        $another->worldwideQuotes->each(static function (WorldwideQuote $quote) use ($opportunity): void {
            $quote->opportunity()->associate($opportunity)->saveQuietly();
        });
    }

    /**
     * @template T of Model
     * @psalm-param T $model
     * @return T
     */
    private static function replicateModelWithTimestamps(Model $model): Model
    {
        return tap($model->replicate(), static function (Model $newModel) use ($model): void {
            $newModel->setCreatedAt($model->{$model->getCreatedAtColumn()});
            $newModel->setUpdatedAt($model->{$model->getUpdatedAtColumn()});
        });
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn(): LoggerInterface => $this->logger = $logger);
    }
}