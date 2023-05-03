<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\User\Models\User;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

final class ChangeQuoteOwnershipData extends Data
{
    public function __construct(
        public readonly string $owner_id,
        public readonly bool $keep_original_owner_as_editor = false,
        public readonly bool $transfer_linked_records_to_new_owner = false,
        public readonly bool $version_ownership = false,
        public readonly ?string $version_id = null,
    ) {
    }

    public function toChangeOwnershipData(): ChangeOwnershipData
    {
        return new ChangeOwnershipData(
            ownerId: $this->owner_id,
            salesUnitId: '',
            keepOriginalOwnerEditor: $this->keep_original_owner_as_editor,
            transferLinkedRecords: $this->transfer_linked_records_to_new_owner,
        );
    }

    public static function rules(...$args): array
    {
        return [
            'owner_id' => ['required', 'uuid', Rule::exists(User::class, 'id')->withoutTrashed()],
        ];
    }

    public static function attributes(...$args): array
    {
        return [
            'owner_id' => 'Owner',
        ];
    }
}
