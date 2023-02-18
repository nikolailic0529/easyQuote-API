<?php

namespace App\Domain\User\DataTransferObjects;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UserAsRelationData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $first_name,
        public readonly string|null $middle_name,
        public readonly string $last_name,
        public readonly string|Optional $user_fullname,
        public readonly \DateTimeInterface|Optional|null $created_at,
        public readonly \DateTimeInterface|Optional|null $updated_at,
    ) {
    }
}
