<?php

namespace App\DTO\User;

use App\Models\User;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class CreateUserRelationNoBackrefData extends Data
{
    public function __construct(
        #[Uuid]
        public readonly string $id
    ) {
    }

    public static function rules(...$args): array
    {
        return [
            'id' => [Rule::exists(User::class, (new User())->getKeyName())->withoutTrashed()],
        ];
    }
}