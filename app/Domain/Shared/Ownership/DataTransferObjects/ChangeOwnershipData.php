<?php

namespace App\Domain\Shared\Ownership\DataTransferObjects;

final class ChangeOwnershipData
{
    public function __construct(
        public readonly string $ownerId,
        public readonly string $salesUnitId,
        public readonly bool $keepOriginalOwnerEditor,
        public readonly bool $transferLinkedRecords,
    ) {
    }
}
