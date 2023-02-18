<?php

namespace App\Domain\Address\DataTransferObjects;

use Spatie\LaravelData\Data;

final class ChangeAddressOwnershipData extends Data
{
    public function __construct(
        public readonly string $owner_id,
        public readonly bool $keep_original_owner_as_editor = false,
        public readonly bool $transfer_linked_records_to_new_owner = false,
    ) {
    }
}
