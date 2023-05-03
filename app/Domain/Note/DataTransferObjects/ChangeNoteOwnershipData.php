<?php

namespace App\Domain\Note\DataTransferObjects;

use Spatie\LaravelData\Data;

final class ChangeNoteOwnershipData extends Data
{
    public function __construct(
        public readonly string $owner_id,
        public readonly bool $keep_original_owner_as_editor = false,
        public readonly bool $transfer_linked_records_to_new_owner = false,
    ) {
    }
}
