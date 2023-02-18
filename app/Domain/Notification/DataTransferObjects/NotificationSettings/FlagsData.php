<?php

namespace App\Domain\Notification\DataTransferObjects\NotificationSettings;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class FlagsData extends Data
{
    public function __construct(
        public bool $emailNotif = true,
        public bool $appNotif = true,
    ) {
    }
}
