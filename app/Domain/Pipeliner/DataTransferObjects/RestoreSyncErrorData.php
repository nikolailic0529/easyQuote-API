<?php

namespace App\Domain\Pipeliner\DataTransferObjects;

use App\Domain\Pipeliner\Models\PipelinerSyncError;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Bail;
use Spatie\LaravelData\Data;
use Symfony\Component\Validator\Constraints\Uuid;

final class RestoreSyncErrorData extends Data
{
    public function __construct(
        #[Bail, Uuid]
        public readonly string $id,
    ) {
    }

    public static function rules(...$args): array
    {
        return [
            'id' => [
                Rule::exists(PipelinerSyncError::class)
                    ->withoutTrashed()
                    ->whereNotNull('archived_at'),
            ],
        ];
    }
}
