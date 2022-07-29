<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Attributes\SerializeWith;
use App\Integrations\Pipeliner\Enum\ActivityStatusEnum;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Enum\PriorityEnum;
use App\Integrations\Pipeliner\Serializers\DateTimeSerializer;
use DateTimeImmutable;

final class CreateTaskInput extends BaseInput
{
    public function __construct(public readonly string                                                                                $subject,
                                public readonly string|InputValueEnum                                                                 $unitId,
                                public readonly string|InputValueEnum                                                                 $activityTypeId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                                                                 $ownerId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                                                                 $description = InputValueEnum::Miss,
                                #[SerializeWith(DateTimeSerializer::class, 'Y-m-d')] public readonly DateTimeImmutable|InputValueEnum $dueDate = InputValueEnum::Miss,
                                public readonly PriorityEnum|InputValueEnum                                                           $priority = InputValueEnum::Miss,
                                public readonly ActivityStatusEnum|InputValueEnum                                                     $status = InputValueEnum::Miss,
                                public readonly CreateActivityAccountRelationInputCollection|InputValueEnum                           $accountRelations = InputValueEnum::Miss,
                                public readonly CreateActivityContactRelationInputCollection|InputValueEnum                           $contactRelations = InputValueEnum::Miss,
                                public readonly CreateActivityLeadOpptyRelationInputCollection|InputValueEnum                         $opportunityRelations = InputValueEnum::Miss,
                                public readonly CreateCloudObjectRelationInputCollection|InputValueEnum                               $documents = InputValueEnum::Miss,
                                public readonly CreateTaskRecurrenceInput|InputValueEnum                                              $taskRecurrence = InputValueEnum::Miss,
                                public readonly CreateTaskReminderInput|InputValueEnum                                                $reminder = InputValueEnum::Miss)
    {
    }
}