<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Attributes\SerializeWith;
use App\Domain\Pipeliner\Integration\Enum\ActivityStatusEnum;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Enum\PriorityEnum;
use App\Domain\Pipeliner\Integration\Serializers\DateTimeSerializer;

final class CreateTaskInput extends BaseInput
{
    public function __construct(
        public readonly string $subject,
        public readonly string|InputValueEnum $unitId,
        public readonly string|InputValueEnum $activityTypeId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $ownerId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $description = InputValueEnum::Miss,
        #[SerializeWith(DateTimeSerializer::class, 'Y-m-d')] public readonly \DateTimeImmutable|InputValueEnum $dueDate = InputValueEnum::Miss,
        public readonly PriorityEnum|InputValueEnum $priority = InputValueEnum::Miss,
        public readonly ActivityStatusEnum|InputValueEnum $status = InputValueEnum::Miss,
        public readonly CreateActivityAccountRelationInputCollection|InputValueEnum $accountRelations = InputValueEnum::Miss,
        public readonly CreateActivityContactRelationInputCollection|InputValueEnum $contactRelations = InputValueEnum::Miss,
        public readonly CreateActivityLeadOpptyRelationInputCollection|InputValueEnum $opportunityRelations = InputValueEnum::Miss,
        public readonly CreateCloudObjectRelationInputCollection|InputValueEnum $documents = InputValueEnum::Miss,
        public readonly CreateTaskRecurrenceInput|InputValueEnum $taskRecurrence = InputValueEnum::Miss,
        public readonly CreateTaskReminderInput|InputValueEnum $reminder = InputValueEnum::Miss
    ) {
    }
}
