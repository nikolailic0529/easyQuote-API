<?php

namespace App\Domain\Asset\DataTransferObjects;

use App\Domain\Shared\Ownership\DataTransferObjects\ChangeOwnershipData;
use App\Domain\User\Models\User;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

final class ChangeAssetOwnershipData extends Data
{
    public function __construct(
        public readonly string $owner_id,
        public readonly bool $keep_original_owner_as_editor = false,
        public readonly bool $transfer_linked_records_to_new_owner = false,
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
