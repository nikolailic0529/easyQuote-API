<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\ActivityStatusEnum;
use DateTimeImmutable;

/**
 * @property-read ActivityAccountRelationEntity[] $accountRelations
 * @property-read ActivityContactRelationEntity[] $contactRelations
 * @property-read ActivityLeadOpptyRelationEntity[] $opportunityRelations
 * @property-read ActivityClientRelationEntity[] $inviteesClients
 * @property-read ActivityContactInviteesRelationEntity[] $inviteesContacts
 * @property-read CloudObjectEntity[] $documents
 */
class AppointmentEntity
{
    public function __construct(
        public readonly string $id,
        public readonly AppointmentTypeEntity $activityType,
        public readonly ClientEntity $owner,
        public readonly SalesUnitEntity|null $unit,
        public readonly string $subject,
        public readonly string $description,
        public readonly string $location,
        public readonly DateTimeImmutable $startDate,
        public readonly DateTimeImmutable $endDate,
        public readonly ?AppointmentReminderEntity $reminder,
        public readonly ActivityStatusEnum $status,
        public readonly array $accountRelations,
        public readonly array $contactRelations,
        public readonly array $opportunityRelations,
        public readonly array $inviteesClients,
        public readonly array $inviteesContacts,
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
            activityType: AppointmentTypeEntity::fromArray($array['activityType']),
            owner: ClientEntity::fromArray($array['owner']),
            unit: SalesUnitEntity::tryFromArray($array['unit']),
            subject: $array['subject'],
            description: $array['description'],
            location: $array['location'],
            startDate: Entity::parseDateTime($array['startDate']),
            endDate: Entity::parseDateTime($array['endDate']),
            reminder: AppointmentReminderEntity::tryFromArray($array['reminder']),
            status: ActivityStatusEnum::from($array['status']),
            accountRelations: array_map(ActivityAccountRelationEntity::fromArray(...),
                array_column($array['accountRelations']['edges'], 'node')),
            contactRelations: array_map(ActivityContactRelationEntity::fromArray(...),
                array_column($array['contactRelations']['edges'], 'node')),
            opportunityRelations: array_map(ActivityLeadOpptyRelationEntity::fromArray(...),
                array_column($array['opportunityRelations']['edges'], 'node')),
            inviteesClients: array_map(ActivityClientRelationEntity::fromArray(...),
                array_column($array['inviteesClients']['edges'], 'node')),
            inviteesContacts: array_map(ActivityContactInviteesRelationEntity::fromArray(...),
                array_column($array['inviteesContacts']['edges'], 'node')),
            documents: array_map(CloudObjectEntity::fromArray(...),
                data_get($array, 'documents.edges.*.node.cloudObject', [])),
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision'],
        );
    }
}