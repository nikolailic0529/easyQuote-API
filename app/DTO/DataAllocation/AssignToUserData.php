<?php

namespace App\DTO\DataAllocation;

use App\Models\User;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Bail;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class AssignToUserData extends Data
{
    #[Bail, Uuid]
    public string $id;

    public static function rules(...$args): array
    {
        return [
            'id' => [Rule::exists(User::class, (new User())->getKeyName())->withoutTrashed()]
        ];
    }
}