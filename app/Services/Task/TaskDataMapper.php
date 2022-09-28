<?php

namespace App\Services\Task;

use App\Contracts\CauserAware;
use App\Enum\Priority;
use App\Enum\ReminderStatus;
use App\Enum\TaskTypeEnum;
use App\Integrations\Pipeliner\Enum\DateDayEnum;
use App\Integrations\Pipeliner\Enum\DateMonthEnum;
use App\Integrations\Pipeliner\Enum\DateWeekEnum;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Enum\PriorityEnum;
use App\Integrations\Pipeliner\Enum\RecurrenceTypeEnum;
use App\Integrations\Pipeliner\Enum\ReminderStatusEnum;
use App\Integrations\Pipeliner\Models\CreateActivityAccountRelationInput;
use App\Integrations\Pipeliner\Models\CreateActivityAccountRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateActivityContactRelationInput;
use App\Integrations\Pipeliner\Models\CreateActivityContactRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateActivityLeadOpptyRelationInput;
use App\Integrations\Pipeliner\Models\CreateActivityLeadOpptyRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateCloudObjectRelationInput;
use App\Integrations\Pipeliner\Models\CreateCloudObjectRelationInputCollection;
use App\Integrations\Pipeliner\Models\CreateTaskInput;
use App\Integrations\Pipeliner\Models\CreateTaskRecurrenceInput;
use App\Integrations\Pipeliner\Models\CreateTaskReminderInput;
use App\Integrations\Pipeliner\Models\RemoveReminderTaskInput;
use App\Integrations\Pipeliner\Models\SalesUnitEntity;
use App\Integrations\Pipeliner\Models\SetReminderTaskInput;
use App\Integrations\Pipeliner\Models\TaskEntity;
use App\Integrations\Pipeliner\Models\UpdateTaskInput;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DateDay;
use App\Models\DateMonth;
use App\Models\DateWeek;
use App\Models\Opportunity;
use App\Models\RecurrenceType;
use App\Models\SalesUnit;
use App\Models\Task\Task;
use App\Models\Task\TaskRecurrence;
use App\Models\Task\TaskReminder;
use App\Models\User;
use App\Repositories\TaskTemplate\QuoteTaskTemplateStore;
use App\Services\Attachment\AttachmentDataMapper;
use App\Services\Pipeliner\PipelinerClientEntityToUserProjector;
use App\Services\Pipeliner\CachedSalesUnitResolver;
use App\Services\Pipeliner\CachedTaskTypeResolver;
use App\Services\Template\Models\TemplateControlFilter;
use App\Services\Template\TemplateSchemaDataMapper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class TaskDataMapper implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(protected QuoteTaskTemplateStore         $taskTemplateStore,
                                protected TemplateSchemaDataMapper       $templateSchemaMapper,
                                protected CachedSalesUnitResolver $salesUnitResolver,
                                protected CachedTaskTypeResolver  $pipelinerTaskTypeResolver,
                                protected AttachmentDataMapper           $attachmentDataMapper)
    {
    }

    public function mapPipelinerCreateTaskInput(Task $task): CreateTaskInput
    {
        $attributes = [];

        if (null !== $task->user) {
            $attributes['ownerId'] = (string)$task->user->pl_reference;
        }

        $attributes['activityTypeId'] = ($this->pipelinerTaskTypeResolver)($task->activity_type->value)?->id ?? InputValueEnum::Miss;
        $attributes['subject'] = $task->name;
        $attributes['description'] = $this->getDescriptionFromTaskContent($task->content);
        $attributes['dueDate'] = $task->expiry_date?->toDateTimeImmutable() ?? InputValueEnum::Miss;
        $attributes['priority'] = match ($task->priority) {
            Priority::Low => PriorityEnum::Low,
            Priority::Medium => PriorityEnum::Medium,
            Priority::High => PriorityEnum::High,
        };
        $attributes['reminder'] = isset($task->reminder)
            ? value($this->mapPipelinerCreateTaskReminderInput(...), $task->reminder, $task->user)
            : InputValueEnum::Miss;
        $attributes['taskRecurrence'] = isset($task->recurrence)
            ? value($this->mapPipelinerCreateTaskRecurrenceInput(...), $task->recurrence)
            : InputValueEnum::Miss;

        $accountRelations = $task->companies
            ->reduce(static function (array $relations, Company $company): array {
                if (null !== $company->pl_reference) {
                    $relations[$company->pl_reference] = new CreateActivityAccountRelationInput(accountId: $company->pl_reference);
                }

                return $relations;
            }, []);

        if (count($accountRelations) > 0) {
            $attributes['accountRelations'] = new CreateActivityAccountRelationInputCollection(...array_values($accountRelations));
        }

        $contactRelations = $task->contacts
            ->reduce(static function (array $relations, Contact $contact): array {
                if (null !== $contact->pl_reference) {
                    $relations[$contact->pl_reference] = new CreateActivityContactRelationInput(contactId: $contact->pl_reference);
                }

                return $relations;
            }, []);

        if (count($contactRelations) > 0) {
            $attributes['contactRelations'] = new CreateActivityContactRelationInputCollection(...array_values($contactRelations));
        }

        $opportunityRelations = $task->opportunities
            ->reduce(static function (array $relations, Opportunity $opportunity): array {
                if (null !== $opportunity->pl_reference) {
                    $relations[$opportunity->pl_reference] = new CreateActivityLeadOpptyRelationInput(leadOpptyId: $opportunity->pl_reference);
                }

                return $relations;
            }, []);

        if (count($opportunityRelations) > 0) {
            $attributes['opportunityRelations'] = new CreateActivityLeadOpptyRelationInputCollection(...array_values($opportunityRelations));
        }

        $attributes['unitId'] = $task->salesUnit?->pl_reference ?? InputValueEnum::Miss;
        $attributes['documents'] = $task->attachments
            ->whereNotNull('pl_reference')
            ->values()
            ->map(function (Attachment $attachment): CreateCloudObjectRelationInput {
                return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
            })
            ->whenNotEmpty(
                static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                    return new CreateCloudObjectRelationInputCollection(...$collection->all());
                },
                static fn(): InputValueEnum => InputValueEnum::Miss);

        return new CreateTaskInput(...$attributes);
    }

    public function mapPipelinerUpdateTaskInput(Task $task, TaskEntity $entity): UpdateTaskInput
    {
        $attributes = [];

        $attributes['id'] = $entity->id;
        $attributes['unitId'] = value(static function (?SalesUnit $unit, ?SalesUnitEntity $entity): InputValueEnum|string {
            if (null === $unit || $unit->pl_reference === $entity?->id) {
                return InputValueEnum::Miss;
            }

            return $unit->pl_reference ?? InputValueEnum::Miss;
        }, $task->salesUnit, $entity->unit);
        $attributes['activityTypeId'] = ($this->pipelinerTaskTypeResolver)($task->activity_type->value)?->id ?? InputValueEnum::Miss;
        $attributes['subject'] = $task->name;
        $attributes['description'] = $this->getDescriptionFromTaskContent($task->content);
        $attributes['dueDate'] = $task->expiry_date?->toDateTimeImmutable() ?? InputValueEnum::Miss;
        $attributes['priority'] = match ($task->priority) {
            Priority::Low => PriorityEnum::Low,
            Priority::Medium => PriorityEnum::Medium,
            Priority::High => PriorityEnum::High,
        };

        $accountRelations = $task->companies
            ->reduce(static function (array $relations, Company $company): array {
                if (null !== $company->pl_reference) {
                    $relations[$company->pl_reference] = new CreateActivityAccountRelationInput(accountId: $company->pl_reference);
                }

                return $relations;
            }, []);

        $attributes['accountRelations'] = value(static function () use (
            $accountRelations,
            $entity
        ): CreateActivityAccountRelationInputCollection|InputValueEnum {
            $currentRelations = collect($entity->accountRelations)->pluck('accountId');
            $updatedRelations = collect($accountRelations)->pluck('accountId');

            if ($currentRelations->count() !== $updatedRelations->count()) {
                return new CreateActivityAccountRelationInputCollection(...array_values($accountRelations));
            }

            if ($currentRelations->diff($updatedRelations) !== $updatedRelations->diff($currentRelations)) {
                return new CreateActivityAccountRelationInputCollection(...array_values($accountRelations));
            }

            return InputValueEnum::Miss;
        });

        $contactRelations = $task->contacts
            ->reduce(static function (array $relations, Contact $contact): array {
                if (null !== $contact->pl_reference) {
                    $relations[$contact->pl_reference] = new CreateActivityContactRelationInput(contactId: $contact->pl_reference);
                }

                return $relations;
            }, []);

        $attributes['contactRelations'] = value(static function () use (
            $contactRelations,
            $entity
        ): CreateActivityContactRelationInputCollection|InputValueEnum {
            $currentRelations = collect($entity->contactRelations)->pluck('accountId');
            $updatedRelations = collect($contactRelations)->pluck('accountId');

            if ($currentRelations->count() !== $updatedRelations->count()) {
                return new CreateActivityContactRelationInputCollection(...array_values($contactRelations));
            }

            if ($currentRelations->diff($updatedRelations) !== $updatedRelations->diff($currentRelations)) {
                return new CreateActivityContactRelationInputCollection(...array_values($contactRelations));
            }

            return InputValueEnum::Miss;
        });

        $opportunityRelations = $task->opportunities
            ->reduce(static function (array $relations, Opportunity $opportunity): array {
                if (null !== $opportunity->pl_reference) {
                    $relations[$opportunity->pl_reference] = new CreateActivityLeadOpptyRelationInput(leadOpptyId: $opportunity->pl_reference);
                }

                return $relations;
            }, []);

        $attributes['opportunityRelations'] = value(static function () use (
            $opportunityRelations,
            $entity
        ): CreateActivityLeadOpptyRelationInputCollection|InputValueEnum {
            $currentRelations = collect($entity->opportunityRelations)->pluck('accountId');
            $updatedRelations = collect($opportunityRelations)->pluck('accountId');

            if ($currentRelations->count() !== $updatedRelations->count()) {
                return new CreateActivityLeadOpptyRelationInputCollection(...array_values($opportunityRelations));
            }

            if ($currentRelations->diff($updatedRelations) !== $updatedRelations->diff($currentRelations)) {
                return new CreateActivityLeadOpptyRelationInputCollection(...array_values($opportunityRelations));
            }

            return InputValueEnum::Miss;
        });
        $attributes['documents'] = $task->attachments
            ->whereNotNull('pl_reference')
            ->values()
            ->map(function (Attachment $attachment): CreateCloudObjectRelationInput {
                return new CreateCloudObjectRelationInput(cloudObjectId: $attachment->pl_reference);
            })
            ->whenNotEmpty(
                static function (BaseCollection $collection): CreateCloudObjectRelationInputCollection {
                    return new CreateCloudObjectRelationInputCollection(...$collection->all());
                },
                static fn(): InputValueEnum => InputValueEnum::Miss);
        $attributes['taskRecurrence'] = isset($task->recurrence)
            ? value($this->mapPipelinerCreateTaskRecurrenceInput(...), $task->recurrence)
            : null;

        return new UpdateTaskInput(...$attributes);
    }

    public function mapPipelinerCreateTaskReminderInput(TaskReminder $reminder, ?User $defaultOwner): CreateTaskReminderInput
    {
        return new CreateTaskReminderInput(
            ownerId: (string)($reminder->user?->pl_reference ?? $defaultOwner?->pl_reference),
            setDate: Carbon::instance($reminder->set_date)->toDateTimeImmutable(),
            status: ReminderStatusEnum::from($reminder->status->name),
        );
    }

    public function mapPipelinerSetReminderTaskInput(TaskReminder $reminder, Task $task): SetReminderTaskInput
    {
        return new SetReminderTaskInput(
            id: $task->pl_reference,
            setDate: Carbon::instance($reminder->set_date)->toDateTimeImmutable(),
            status: ReminderStatusEnum::from($reminder->status->name),
        );
    }

    public function mapPipelinerRemoveReminderTaskInput(Task $task): RemoveReminderTaskInput
    {
        return new RemoveReminderTaskInput(
            id: $task->pl_reference,
        );
    }

    public function mapPipelinerCreateTaskRecurrenceInput(TaskRecurrence $recurrence): CreateTaskRecurrenceInput
    {
        $recurrenceAttributes = [];

        $recurrenceAttributes['startDate'] = Carbon::instance($recurrence->start_date)->toDateTimeImmutable();
        $recurrenceAttributes['endDate'] = isset($recurrence->end_date) ? Carbon::instance($recurrence->end_date)->toDateTimeImmutable() : InputValueEnum::Miss;
        $recurrenceAttributes['type'] = RecurrenceTypeEnum::from($recurrence->type->value);
        $recurrenceAttributes['day'] = DateDayEnum::from($recurrence->day->toEnum()->value);
        $recurrenceAttributes['week'] = DateWeekEnum::from($recurrence->week->toEnum()->value);
        $recurrenceAttributes['month'] = DateMonthEnum::from($recurrence->month->toEnum()->value);
        $recurrenceAttributes['dayOfWeek'] = $recurrence->day_of_week ?? InputValueEnum::Miss;
        $recurrenceAttributes['occurrencesCount'] = $recurrence->occurrences_count ?? InputValueEnum::Miss;
        $recurrenceAttributes['occurEvery'] = $recurrence->occur_every ?? InputValueEnum::Miss;

        return new CreateTaskRecurrenceInput(...$recurrenceAttributes);
    }

    public function mapFromTaskEntity(TaskEntity $entity): Task
    {
        return tap(new Task(), function (Task $task) use ($entity): void {
            $task->{$task->getKeyName()} = (string)Uuid::generate(4);

            if (null !== $entity->unit) {
                $task->salesUnit()->associate(
                    SalesUnit::query()->where('unit_name', $entity->unit->name)->first()
                );
            }

            $task->pl_reference = $entity->id;
            $task->activity_type = TaskTypeEnum::tryFrom($entity->activityType->name) ?? TaskTypeEnum::Task;
            $task->name = $entity->subject;

            $task->content = tap($this->taskTemplateStore->all(), function (array &$tpl) use ($entity): void {
                $this->setDescriptionInTemplate($entity->description, $tpl);
                $this->setStatusInTemplate(Str::headline($entity->status->name), $tpl);
            });

            $task->priority = match ($entity->priority) {
                PriorityEnum::Low => Priority::Low,
                PriorityEnum::Medium => Priority::Medium,
                PriorityEnum::High => Priority::High,
            };
            $task->expiry_date = isset($entity->dueDate)
                ? Carbon::instance($entity->dueDate)
                : null;

            if (null !== $entity->owner) {
                $task->user()->associate(PipelinerClientEntityToUserProjector::from($entity->owner)());
            }

            $reminder = isset($entity->reminder)
                ? tap(new TaskReminder(), static function (TaskReminder $reminder) use ($task, $entity): void {
                    $reminder->task()->associate($task);

                    $reminder->set_date = Carbon::instance($entity->reminder->setDate);
                    $reminder->status = match ($entity->reminder->status) {
                        ReminderStatusEnum::Snoozed => ReminderStatus::Snoozed,
                        ReminderStatusEnum::Scheduled => ReminderStatus::Scheduled,
                        ReminderStatusEnum::Dismissed => ReminderStatus::Dismissed,
                    };
                })
                : null;

            $recurrence = isset($entity->taskRecurrence)
                ? tap(new TaskRecurrence(), static function (TaskRecurrence $recurrence) use ($task, $entity): void {
                    $recurrence->task()->associate($task);

                    $recurrence->type()->associate(RecurrenceType::query()->where('value', $entity->taskRecurrence->type)->sole());
                    $recurrence->day()->associate(DateDay::query()->where('value', $entity->taskRecurrence->day)->sole());
                    $recurrence->week()->associate(DateWeek::query()->where('value', $entity->taskRecurrence->week)->sole());
                    $recurrence->month()->associate(DateMonth::query()->where('value', $entity->taskRecurrence->month)->sole());
                    $recurrence->day_of_week = $entity->taskRecurrence->dayOfWeek;
                    $recurrence->occur_every = $entity->taskRecurrence->occurEvery;
                    $recurrence->occurrences_count = $entity->taskRecurrence->occurrencesCount;

                    $recurrence->start_date = Carbon::instance($entity->taskRecurrence->startDate);
                    $recurrence->end_date = isset($entity->taskRecurrence->endDate)
                        ? Carbon::instance($entity->taskRecurrence->endDate)
                        : null;
                })
                : null;

            $task->setRelation('reminder', $reminder);
            $task->setRelation('recurrence', $recurrence);

            $task->setRelation('companies', value(function () use ($entity): Collection {
                $relations = new Collection();

                foreach ($entity->accountRelations as $accountRelation) {
                    $account = Company::query()->where('pl_reference', $accountRelation->accountId)->first();

                    if (null !== $account) {
                        $relations[] = $account;
                    }
                }

                return $relations;
            }));

            $task->setRelation('contacts', value(function () use ($entity): Collection {
                $relations = new Collection();

                foreach ($entity->contactRelations as $contactRelation) {
                    $contact = Contact::query()->where('pl_reference', $contactRelation->contactId)->first();

                    if (null !== $contact) {
                        $relations[] = $contact;
                    }
                }

                return $relations;
            }));

            $task->setRelation('opportunities', value(function () use ($entity): Collection {
                $relations = new Collection();

                foreach ($entity->opportunityRelations as $opptyRelation) {
                    $oppty = Opportunity::query()->where('pl_reference', $opptyRelation->leadOpptyId)->first();

                    if (null !== $oppty) {
                        $relations[] = $oppty;
                    }
                }

                return $relations;
            }));

            $task->setRelation('attachments', value(function () use ($entity): Collection {
                $relations = new Collection();

                foreach ($entity->documents as $document) {
                    $attachment = Attachment::query()->where('pl_reference', $document->id)->first();

                    if (null !== $attachment) {
                        $relations[] = $attachment;
                    }
                }

                return $relations;
            }));
        });
    }

    public function mergeAttributeFrom(Task $task, Task $another): void
    {
        $toBeMergedAttributes = [
            'activity_type',
            'name',
            'content',
            'priority',
            'expiry_date',
        ];

        foreach ($toBeMergedAttributes as $attribute) {
            if (null !== $another->$attribute) {
                $task->$attribute = $another->$attribute;
            }
        }

        $toBeMergedHasOneRelations = [
            'reminder',
            'recurrence',
        ];

        foreach ($toBeMergedHasOneRelations as $relation) {
            /** @var Model&SoftDeletes|null $relatedOriginal */
            $relatedOriginal = $task->$relation;

            /** @var Model&SoftDeletes|null $relatedOriginal */
            $relatedChanged = $another->$relation;

            if (null === $relatedChanged && null !== $relatedOriginal) {
                $relatedOriginal->{$relatedOriginal->getDeletedAtColumn()} = $relatedOriginal->freshTimestamp();
            } elseif (null !== $another->$relation) {
                $task->setRelation($relation, $relatedChanged->replicate()->task()->associate($task));
            }
        }

        $toBeMergedBelongsToRelations = [
            'salesUnit'
        ];

        foreach ($toBeMergedBelongsToRelations as $relation) {
            if (null !== $another->$relation) {
                $task->$relation()->associate($another->$relation);
            }
        }

        $toBeMergedManyToManyRelations = [
            'companies',
            'contacts',
            'opportunities',
        ];

        foreach ($toBeMergedManyToManyRelations as $relation) {
            /** @var Collection $relatedOriginal */
            $relatedOriginal = $task->$relation;

            /** @var Collection $relatedChanged */
            $relatedChanged = $another->$relation;

            $relatedOriginal->push(...$relatedChanged);
        }
    }

    private function getDescriptionFromTaskContent(array $content): string
    {
        foreach ($content as $col) {

            foreach ($col['child'] as $child) {

                foreach ($child['controls'] as $control) {

                    if ('richtext' === $control['type']) {

                        return (string)$control['value'];

                    }

                }
            }
        }

        return '';
    }

    public function setDescriptionInTemplate(string $description, array &$template): void
    {
        $this->templateSchemaMapper->setControlValue(
            TemplateControlFilter::new()->type('richtext'),
            $description,
            $template,
            1
        );
    }

    public function setStatusInTemplate(string $status, array &$template): void
    {
        $this->templateSchemaMapper->setControlValue(
            TemplateControlFilter::new()
                ->after(TemplateControlFilter::new()->value('status')->type('label'))
                ->type('dropdown'),
            $status,
            $template,
            1
        );
    }


    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }
}