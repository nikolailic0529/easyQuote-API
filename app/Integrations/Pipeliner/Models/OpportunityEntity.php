<?php

namespace App\Integrations\Pipeliner\Models;

use DateTimeImmutable;

/**
 * @property-read LeadOpptyAccountRelationEntity[] $accountRelations
 * @property-read ContactRelationEntity[] $contactRelations
 * @property-read CloudObjectEntity[] $documents
 */
class OpportunityEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $status,
        public readonly ?DateTimeImmutable $closingDate,
        public readonly ?DateTimeImmutable $qualifyDate,
        public readonly ?DateTimeImmutable $lostDate,
        public readonly ?DateTimeImmutable $wonDate,
        public readonly string $quickAccountEmail,
        public readonly string $quickAccountName,
        public readonly string $quickAccountPhone,
        public readonly string $quickContactName,
        public readonly string $quickEmail,
        public readonly string $quickPhone,
        public readonly int $ranking,
        public readonly CurrencyForeignField $value,
        public readonly SalesUnitEntity $unit,
        public readonly StepEntity $step,
        public readonly ClientEntity $owner,
        public readonly ?AccountEntity $primaryAccount,
        public readonly ?ContactEntity $primaryContact,
        public readonly ?CurrencyEntity $productCurrency,
        public readonly array $accountRelations,
        public readonly array $contactRelations,
        public readonly array $documents,
        public readonly array $customFields,
        public readonly bool $isArchived,
        public readonly DateTimeImmutable $created,
        public readonly DateTimeImmutable $modified,
        public readonly int $revision
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            name: $array['name'],
            description: $array['description'],
            status: $array['status'],
            closingDate: Entity::parseDateTime($array['closingDate'] ?? null),
            qualifyDate: Entity::parseDateTime($array['qualifyDate'] ?? null),
            lostDate: Entity::parseDateTime($array['lostDate'] ?? null),
            wonDate: Entity::parseDateTime($array['wonDate'] ?? null),
            quickAccountEmail: $array['quickAccountEmail'],
            quickAccountName: $array['quickAccountName'],
            quickAccountPhone: $array['quickAccountPhone'],
            quickContactName: $array['quickContactName'],
            quickEmail: $array['quickEmail'],
            quickPhone: $array['quickPhone'],
            ranking: $array['ranking'],
            value: CurrencyForeignField::fromArray($array['value']),
            unit: SalesUnitEntity::fromArray($array['unit']),
            step: StepEntity::fromArray($array['step']),
            owner: ClientEntity::fromArray($array['owner']),
            primaryAccount: AccountEntity::tryFromArray($array['primaryAccount']),
            primaryContact: ContactEntity::tryFromArray($array['primaryContact'] ?? null),
            productCurrency: CurrencyEntity::tryFromArray($array['productCurrency'] ?? null),
            accountRelations: array_map(LeadOpptyAccountRelationEntity::fromArray(...),
                array_column($array['accountRelations']['edges'], 'node')),
            contactRelations: array_map(ContactRelationEntity::fromArray(...),
                array_column($array['contactRelations']['edges'], 'node')),
            documents: array_map(CloudObjectEntity::fromArray(...),
                data_get($array, 'documents.edges.*.node.cloudObject', [])),
            customFields: json_decode($array['customFields'], true),
            isArchived: $array['isArchived'],
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision'],
        );
    }
}