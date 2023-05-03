<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\EntityLacotaType;

class EntityFilterRelatedField extends BaseFilterInput
{
    protected function __construct(
        public readonly EntityLacotaType $entity,
        public readonly array $entityIds
    ) {
    }

    public static function account(string $entityId, string ...$entityIds): static
    {
        return new static(EntityLacotaType::Account, [$entityId, ...$entityIds]);
    }

    public static function contact(string $entityId, string ...$entityIds): static
    {
        return new static(EntityLacotaType::Contact, [$entityId, ...$entityIds]);
    }

    public static function task(string $entityId, string ...$entityIds): static
    {
        return new static(EntityLacotaType::Task, [$entityId, ...$entityIds]);
    }

    public static function appointment(string $entityId, string ...$entityIds): static
    {
        return new static(EntityLacotaType::Appointment, [$entityId, ...$entityIds]);
    }

    public static function lead(string $entityId, string ...$entityIds): static
    {
        return new static(EntityLacotaType::Lead, [$entityId, ...$entityIds]);
    }

    public static function opportunity(string $entityId, string ...$entityIds): static
    {
        return new static(EntityLacotaType::Opportunity, [$entityId, ...$entityIds]);
    }

    public static function project(string $entityId, string ...$entityIds): static
    {
        return new static(EntityLacotaType::Project, [$entityId, ...$entityIds]);
    }

    public function jsonSerialize(): array
    {
        return [
            'entity' => $this->entity->name,
            'entityIds' => $this->entityIds,
        ];
    }
}
