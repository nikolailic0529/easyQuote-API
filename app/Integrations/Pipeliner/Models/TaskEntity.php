<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\ActivityStatusEnum;
use App\Integrations\Pipeliner\Enum\PriorityEnum;
use DateTimeImmutable;

/**
 * @property-read ActivityAccountRelationEntity[] $accountRelations
 * @property-read ActivityContactRelationEntity[] $contactRelations
 * @property-read ActivityLeadOpptyRelationEntity[] $opportunityRelations
 * @property-read CloudObjectEntity[] $documents
 */
class TaskEntity
{
    public function __construct(
        public readonly string $id,
        public readonly TaskTypeEntity $activityType,
        public readonly ClientEntity|null $owner,
        public readonly SalesUnitEntity|null $unit,
        public readonly string $subject,
        public readonly string $description,
        public readonly ActivityStatusEnum $status,
        public readonly PriorityEnum $priority,
        public readonly ?DateTimeImmutable $dueDate,
        public readonly ?TaskRecurrenceEntity $taskRecurrence,
        public readonly ?TaskReminderEntity $reminder,
        public readonly array $accountRelations,
        public readonly array $contactRelations,
        public readonly array $opportunityRelations,
        public readonly array $documents,
        public readonly DateTimeImmutable $created,
        public readonly DateTimeImmutable $modified,
        public readonly int $revision
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            activityType: TaskTypeEntity::fromArray($array['activityType']),
            owner: ClientEntity::tryFromArray($array['owner']),
            unit: SalesUnitEntity::fromArray($array['unit']),
            subject: $array['subject'],
            description: $array['description'],
            status: ActivityStatusEnum::from($array['status']),
            priority: PriorityEnum::from($array['priority']),
            dueDate: Entity::parseDateTime($array['dueDate']),
            taskRecurrence: TaskRecurrenceEntity::tryFromArray($array['taskRecurrence']),
            reminder: TaskReminderEntity::tryFromArray($array['reminder']),
            accountRelations: array_map(ActivityAccountRelationEntity::fromArray(...),
                array_column($array['accountRelations']['edges'], 'node')),
            contactRelations: array_map(ActivityContactRelationEntity::fromArray(...),
                array_column($array['contactRelations']['edges'], 'node')),
            opportunityRelations: array_map(ActivityLeadOpptyRelationEntity::fromArray(...),
                array_column($array['opportunityRelations']['edges'], 'node')),
            documents: array_map(CloudObjectEntity::fromArray(...),
                data_get($array, 'documents.edges.*.node.cloudObject', [])),
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision'],
        );
    }
}