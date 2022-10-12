<?php

namespace App\Services\Opportunity;

use App\Contracts\CauserAware;
use App\DTO\{MissingValue,
    Opportunity\CreateOpportunityData,
    Opportunity\CreateOpportunityRecurrenceData,
    Opportunity\CreateSupplierData,
    Opportunity\MarkOpportunityAsLostData,
    Opportunity\SetStageOfOpportunityData,
    Opportunity\UpdateOpportunityData,
    Opportunity\UpdateSupplierData};
use App\Enum\Lock;
use App\Enum\OpportunityStatus;
use App\Events\{Opportunity\OpportunityCreated,
    Opportunity\OpportunityDeleted,
    Opportunity\OpportunityMarkedAsLost,
    Opportunity\OpportunityMarkedAsNotLost,
    Opportunity\OpportunityUpdated};
use App\Models\Company;
use App\Models\DateDay;
use App\Models\DateMonth;
use App\Models\DateWeek;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Pipeline\PipelineStage;
use App\Models\RecurrenceType;
use App\Services\Exceptions\ValidationException;
use App\Services\ExchangeRate\CurrencyConverter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Carbon;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class OpportunityEntityService implements CauserAware
{
    const DEFAULT_PL_ID = PL_WWDP;

    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected LockProvider $lockProvider,
        protected ValidatorInterface $validator,
        protected EventDispatcher $eventDispatcher,
        protected CurrencyConverter $currencyConverter,
    ) {
    }

    /**
     * @param  CreateOpportunityData  $data
     * @return Opportunity
     * @throws ValidationException
     * @throws \Throwable
     */
    public function createOpportunity(CreateOpportunityData $data): Opportunity
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($data->create_suppliers as $supplierData) {
            $violations = $this->validator->validate($supplierData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        return tap(new Opportunity(), function (Opportunity $opportunity) use ($data) {
            $opportunity->{$opportunity->getKeyName()} = Uuid::generate(4)->string;
            $opportunity->salesUnit()->associate($data->sales_unit_id);
            $opportunity->pipeline()->associate($data->pipeline_id);
            $opportunity->user()->associate($data->user_id);
            $opportunity->contractType()->associate($data->contract_type_id);
            $opportunity->project_name = $data->project_name;
            $opportunity->primaryAccount()->associate($data->primary_account_id);
            $opportunity->endUser()->associate($data->end_user_id);
            $opportunity->primaryAccountContact()->associate($data->primary_account_contact_id);
            $opportunity->accountManager()->associate($data->account_manager_id);
            $opportunity->pipelineStage()->associate($data->pipeline_stage_id);
            $opportunity->are_end_user_addresses_available = $data->are_end_user_addresses_available;
            $opportunity->are_end_user_contacts_available = $data->are_end_user_contacts_available;
            $opportunity->nature_of_service = $data->nature_of_service;
            $opportunity->renewal_month = $data->renewal_month;
            $opportunity->renewal_year = $data->renewal_year;
            $opportunity->customer_status = $data->customer_status;
            $opportunity->end_user_name = $data->end_user_name;
            $opportunity->hardware_status = $data->hardware_status;
            $opportunity->region_name = $data->region_name;
            $opportunity->opportunity_start_date = $data->opportunity_start_date?->toDateString();
            $opportunity->is_opportunity_start_date_assumed = $data->is_opportunity_start_date_assumed;
            $opportunity->opportunity_end_date = $data->opportunity_end_date?->toDateString();
            $opportunity->is_opportunity_end_date_assumed = $data->is_opportunity_end_date_assumed;
            $opportunity->opportunity_closing_date = $data->opportunity_closing_date?->toDateString();

            $opportunity->contract_duration_months = $data->contract_duration_months;
            $opportunity->is_contract_duration_checked = $data->is_contract_duration_checked;

            $opportunity->expected_order_date = $data->expected_order_date?->toDateString();
            $opportunity->customer_order_date = $data->customer_order_date?->toDateString();
            $opportunity->purchase_order_date = $data->purchase_order_date?->toDateString();
            $opportunity->supplier_order_date = $data->supplier_order_date?->toDateString();
            $opportunity->supplier_order_transaction_date = $data->supplier_order_transaction_date?->toDateString();
            $opportunity->supplier_order_confirmation_date = $data->supplier_order_confirmation_date?->toDateString();
            $opportunity->opportunity_amount = $data->opportunity_amount;
            $opportunity->base_opportunity_amount = transform($data->opportunity_amount, function (float $value) use (
                $data
            ): float {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    fromCode: $data->opportunity_amount_currency_code ?? $baseCurrency,
                    toCode: $baseCurrency,
                    amount: $value
                );
            });
            $opportunity->opportunity_amount_currency_code = $data->opportunity_amount_currency_code;
            $opportunity->purchase_price = $data->purchase_price;
            $opportunity->base_purchase_price = transform($data->purchase_price, function (float $value) use ($data
            ): float {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    fromCode: $data->purchase_price_currency_code ?? $baseCurrency,
                    toCode: $baseCurrency,
                    amount: $value
                );
            });
            $opportunity->purchase_price_currency_code = $data->purchase_price_currency_code;
            $opportunity->estimated_upsell_amount = $data->estimated_upsell_amount;
            $opportunity->estimated_upsell_amount_currency_code = $data->estimated_upsell_amount_currency_code;
            $opportunity->list_price = $data->list_price;
            $opportunity->base_list_price = transform($data->list_price, function (float $value) use ($data): float {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                return $this->currencyConverter->convertCurrencies(
                    fromCode: $data->list_price_currency_code ?? $baseCurrency,
                    toCode: $baseCurrency,
                    amount: $value
                );
            });
            $opportunity->list_price_currency_code = $data->list_price_currency_code;
            $opportunity->personal_rating = $data->personal_rating;
            $opportunity->ranking = $data->ranking;
            $opportunity->margin_value = $data->margin_value;
            $opportunity->service_level_agreement_id = $data->service_level_agreement_id;
            $opportunity->sale_unit_name = $data->sale_unit_name;
            $opportunity->drop_in = $data->drop_in;
            $opportunity->lead_source_name = $data->lead_source_name;
            $opportunity->has_higher_sla = $data->has_higher_sla;
            $opportunity->is_multi_year = $data->is_multi_year;
            $opportunity->has_additional_hardware = $data->has_additional_hardware;
            $opportunity->has_service_credits = $data->has_service_credits;
            $opportunity->remarks = $data->remarks;
            $opportunity->campaign_name = $data->campaign_name;
            $opportunity->competition_name = $data->competition_name;
            $opportunity->notes = $data->notes;

            $recurrence = isset($data->recurrence)
                ? tap(new Opportunity\OpportunityRecurrence(),
                    function (Opportunity\OpportunityRecurrence $recurrence) use (
                        $data,
                        $opportunity
                    ): void {
                        $recurrence->owner()->associate($this->causer);
                        $recurrence->opportunity()->associate($opportunity);
                        $recurrence->stage()->associate($data->recurrence->stage_id);
                        $recurrence->type()->associate(RecurrenceType::query()
                            ->where('value', $data->recurrence->type)
                            ->sole());
                        $recurrence->occur_every = $data->recurrence->occur_every;
                        $recurrence->occurrences_count = $data->recurrence->occurrences_count;
                        $recurrence->start_date = \Carbon\Carbon::instance($data->recurrence->start_date);
                        $recurrence->end_date = isset($data->recurrence->end_date)
                            ? Carbon::instance($data->recurrence->end_date)
                            : null;
                        $recurrence->day()->associate(DateDay::query()->where('value', $data->recurrence->day)->sole());
                        $recurrence->month()->associate(DateMonth::query()
                            ->where('value', $data->recurrence->month)
                            ->sole());
                        $recurrence->week()->associate(DateWeek::query()
                            ->where('value', $data->recurrence->week)
                            ->sole());
                        $recurrence->day_of_week = $data->recurrence->day_of_week;
                        $recurrence->condition = $data->recurrence->condition;
                    })
                : null;

            $suppliers = Collection::make($data->create_suppliers)
                ->values()
                ->map(static function (CreateSupplierData $data, int $i) use ($opportunity): OpportunitySupplier {
                    return tap(new OpportunitySupplier(),
                        function (OpportunitySupplier $supplier) use ($opportunity, $i, $data): void {
                            $supplier->entity_order = $i;
                            $supplier->opportunity()->associate($opportunity);
                            $supplier->forceFill($data->toArray());
                        });
                });

            $opportunity->setRelation('opportunitySuppliers', $suppliers);

            $this->connection->transaction(static function () use ($data, $opportunity, $recurrence): void {
                $opportunity->save();

                $recurrence?->opportunity()?->associate($opportunity)?->save();

                $opportunity->opportunitySuppliers->each->save();
            });

            $this->eventDispatcher->dispatch(
                new OpportunityCreated($opportunity, $this->causer)
            );
        });
    }

    /**
     * @param  Opportunity  $opportunity
     * @param  UpdateOpportunityData  $data
     * @return Opportunity
     * @throws ValidationException
     * @throws \Throwable
     */
    public function updateOpportunity(Opportunity $opportunity, UpdateOpportunityData $data): Opportunity
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($data->create_suppliers as $supplierData) {
            $violations = $this->validator->validate($supplierData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        foreach ($data->update_suppliers as $supplierData) {
            $violations = $this->validator->validate($supplierData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_OPPORTUNITY($opportunity->getKey()),
            10
        );

        return tap($opportunity, function (Opportunity $opportunity) use ($lock, $data) {
            $oldOpportunity = (new Opportunity())->setRawAttributes($opportunity->getRawOriginal());

            $attributes = $data->except('create_suppliers', 'update_suppliers', 'recurrence')->toArray();

            $convertToBase = function (
                float $value,
                string|null|MissingValue $fromCode,
                ?string $originalFromCode
            ): float {
                $baseCurrency = $this->currencyConverter->getBaseCurrency();

                $fromCode = $fromCode instanceof MissingValue ? $originalFromCode : $fromCode;

                return $this->currencyConverter->convertCurrencies(
                    fromCode: $fromCode ?? $baseCurrency,
                    toCode: $baseCurrency,
                    amount: $value
                );
            };

            if (!$data->opportunity_amount instanceof MissingValue) {
                $attributes['base_opportunity_amount'] = isset($data->opportunity_amount)
                    ? $convertToBase($data->opportunity_amount, $data->opportunity_amount_currency_code,
                        $opportunity->opportunity_amount_currency_code)
                    : null;
            }

            if (!$data->purchase_price instanceof MissingValue) {
                $attributes['base_purchase_price'] = isset($data->purchase_price)
                    ? $convertToBase($data->purchase_price, $data->purchase_price_currency_code,
                        $opportunity->purchase_price_currency_code)
                    : null;
            }

            if (!$data->list_price instanceof MissingValue) {
                $attributes['base_list_price'] = isset($data->list_price)
                    ? $convertToBase($data->list_price, $data->list_price_currency_code,
                        $opportunity->list_price_currency_code)
                    : null;
            }

            foreach ($attributes as $attr => $value) {
                if (!$value instanceof MissingValue) {
                    $opportunity->$attr = $value;
                }
            }

            $recurrence = new MissingValue();

            if ($data->recurrence instanceof CreateOpportunityRecurrenceData) {
                $recurrence = tap($opportunity->recurrence ?? new Opportunity\OpportunityRecurrence(),
                    function (Opportunity\OpportunityRecurrence $recurrence) use (
                        $data,
                        $opportunity
                    ): void {
                        $recurrence->owner()->associate($this->causer);
                        $recurrence->opportunity()->associate($opportunity);
                        $recurrence->stage()->associate($data->recurrence->stage_id);
                        $recurrence->type()->associate(RecurrenceType::query()
                            ->where('value', $data->recurrence->type)
                            ->sole());
                        $recurrence->occur_every = $data->recurrence->occur_every;
                        $recurrence->occurrences_count = $data->recurrence->occurrences_count;
                        $recurrence->start_date = \Carbon\Carbon::instance($data->recurrence->start_date);
                        $recurrence->end_date = isset($data->recurrence->end_date)
                            ? Carbon::instance($data->recurrence->end_date)
                            : null;
                        $recurrence->day()->associate(DateDay::query()->where('value', $data->recurrence->day)->sole());
                        $recurrence->month()->associate(DateMonth::query()
                            ->where('value', $data->recurrence->month)
                            ->sole());
                        $recurrence->week()->associate(DateWeek::query()
                            ->where('value', $data->recurrence->week)
                            ->sole());
                        $recurrence->day_of_week = $data->recurrence->day_of_week;
                        $recurrence->condition = $data->recurrence->condition;
                    });
            } elseif (null === $data->recurrence) {
                $recurrence = null;
            }

            if (is_array($data->update_suppliers)) {
                $supplierDataMap = collect($data->update_suppliers)
                    ->mapWithKeys(static fn(UpdateSupplierData $supplier
                    ): array => [$supplier->supplier_id => $supplier]);

                $opportunity->opportunitySuppliers->each(static function (OpportunitySupplier $supplier) use (
                    $supplierDataMap
                ): void {
                    if ($supplierDataMap->has($supplier->getKey()) === false) {
                        $supplier->{$supplier->getDeletedAtColumn()} = $supplier->freshTimestamp();
                        return;
                    }

                    /** @var UpdateSupplierData $data */
                    $data = $supplierDataMap->get($supplier->getKey());

                    $supplier->forceFill($data->except('supplier_id')->toArray());
                });
            }

            if (is_array($data->create_suppliers)) {
                collect($data->create_suppliers)
                    ->each(static function (CreateSupplierData $data) use ($opportunity): void {
                        $supplier = tap(new OpportunitySupplier(),
                            static function (OpportunitySupplier $supplier) use ($opportunity, $data): void {
                                $supplier->{$supplier->getKeyName()} = (string) Uuid::generate(4);
                                $supplier->opportunity_id = $opportunity->getKey();
                                $supplier->supplier_name = $data->supplier_name;
                                $supplier->country_name = $data->country_name;
                                $supplier->contact_name = $data->contact_name;
                                $supplier->contact_email = $data->contact_email;
                                $supplier->{$supplier->getCreatedAtColumn()} = $supplier->freshTimestampString();
                                $supplier->{$supplier->getUpdatedAtColumn()} = $supplier->freshTimestampString();
                            });

                        $opportunity->opportunitySuppliers->push($supplier);
                    });
            }

            $opportunity->opportunitySuppliers
                ->values()
                ->each(static function (OpportunitySupplier $supplier, int $i): void {
                    $supplier->entity_order = $i;
                });

            $lock->block(30, function () use ($opportunity, $recurrence, $data) {
                $this->connection->transaction(static function () use ($recurrence, $data, $opportunity): void {
                    $opportunity->save();
                    $opportunity->opportunitySuppliers->each->save();

                    // Delete recurrence from the task if null provided
                    if (null === $recurrence) {
                        $opportunity->recurrence?->delete();
                    } elseif ($recurrence instanceof Opportunity\OpportunityRecurrence) {
                        $recurrence->save();
                    }
                });
            });

            $this->eventDispatcher->dispatch(
                new OpportunityUpdated($opportunity, $oldOpportunity, $this->causer),
            );
        });
    }

    /**
     * @param  Opportunity  $opportunity
     * @param  SetStageOfOpportunityData  $data
     * @return Opportunity
     * @throws ValidationException|\Throwable
     */
    public function setStageOfOpportunity(Opportunity $opportunity, SetStageOfOpportunityData $data): Opportunity
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($opportunity, function (Opportunity $opportunity) use ($data): void {

            /** @var PipelineStage $stage */
            $stage = PipelineStage::query()->findOrFail($data->stage_id);

            $oppsOfStage = Opportunity::query()
                ->whereBelongsTo($stage->pipeline)
                ->whereBelongsTo($stage)
                ->whereKeyNot($opportunity)
                ->orderByRaw("isnull(order_in_pipeline_stage) asc")
                ->orderBy('order_in_pipeline_stage')
                ->latest()
                ->select([
                    $opportunity->getQualifiedKeyName(),
                    $opportunity->pipeline()->getQualifiedForeignKeyName(),
                    $opportunity->pipelineStage()->getQualifiedForeignKeyName(),
                    $opportunity->qualifyColumn('order_in_pipeline_stage'),
                    $opportunity->qualifyColumn('sale_action_name'),
                    $opportunity->getQualifiedCreatedAtColumn(),
                    $opportunity->getQualifiedUpdatedAtColumn(),
                ])
                ->get();

            $opportunity->pipelineStage()->associate($stage);
            $opportunity->order_in_pipeline_stage = $data->order_in_stage;
            $opportunity->sale_action_name = $stage->qualified_stage_name;

            $oppsOfStage->splice($data->order_in_stage, 0, [$opportunity]);

            $oppsOfStage->values()->each(static function (Opportunity $opp, int $i): void {
                $opp->order_in_pipeline_stage = $i;
            });

            foreach ($oppsOfStage as $opp) {
                $this->lockProvider->lock(
                    Lock::UPDATE_OPPORTUNITY($opp->getKey()),
                    10
                )
                    ->block(30, function () use ($opp) {
                        $this->connection->transaction(static fn() => $opp->save());
                    });
            }
        });


    }

    /**
     * @param  Opportunity  $opportunity
     */
    public function deleteOpportunity(Opportunity $opportunity): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_OPPORTUNITY($opportunity->getKey()),
            10
        );

        $lock->block(30, function () use ($opportunity) {

            $this->connection->transaction(static fn() => $opportunity->delete());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityDeleted($opportunity, $this->causer)
        );
    }

    /**
     * @param  Opportunity  $opportunity
     * @param  MarkOpportunityAsLostData  $data
     */
    public function markOpportunityAsLost(Opportunity $opportunity, MarkOpportunityAsLostData $data): void
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_OPPORTUNITY($opportunity->getKey()),
            10
        );

        $opportunity->status = OpportunityStatus::LOST;
        $opportunity->status_reason = $data->status_reason;

        $lock->block(30, function () use ($opportunity) {

            $this->connection->transaction(static fn() => $opportunity->save());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityMarkedAsLost($opportunity, $this->causer)
        );
    }

    public function markOpportunityAsNotLost(Opportunity $opportunity): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_OPPORTUNITY($opportunity->getKey()),
            10
        );

        $opportunity->status = OpportunityStatus::NOT_LOST;
        $opportunity->status_reason = null;

        $lock->block(30, function () use ($opportunity) {

            $this->connection->transaction(static fn() => $opportunity->save());

        });

        $this->eventDispatcher->dispatch(
            new OpportunityMarkedAsNotLost($opportunity, $this->causer)
        );
    }

    public function syncPrimaryAccountContacts(Company $primaryAccount): void
    {
        $opportunityModel = (new Opportunity());

        // Set primary account contact to null,
        // where primary account contact was detached from the corresponding primary account.
        $opportunityModel->newQuery()
            ->where($opportunityModel->primaryAccount()->getQualifiedForeignKeyName(), $primaryAccount->getKey())
            ->whereNotNull($opportunityModel->primaryAccountContact()->getQualifiedForeignKeyName())
            ->whereNotExists(static function (BaseBuilder $builder) use ($opportunityModel, $primaryAccount) {
                $builder->selectRaw(1)
                    ->from($primaryAccount->contacts()->getTable())
                    ->whereColumn($primaryAccount->contacts()->getQualifiedRelatedPivotKeyName(),
                        $opportunityModel->primaryAccountContact()->getQualifiedForeignKeyName())
                    ->where($primaryAccount->contacts()->getQualifiedForeignPivotKeyName(), $primaryAccount->getKey());
            })
            ->update([$opportunityModel->primaryAccountContact()->getForeignKeyName() => null]);
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}
