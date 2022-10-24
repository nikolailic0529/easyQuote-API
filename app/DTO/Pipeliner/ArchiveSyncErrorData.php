<?php

namespace App\DTO\Pipeliner;

use App\Models\Pipeliner\PipelinerSyncError;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Bail;
use Spatie\LaravelData\Data;
use Symfony\Component\Validator\Constraints\Uuid;

final class ArchiveSyncErrorData extends Data
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
                    ->whereNull('archived_at'),
            ],
        ];
    }
}