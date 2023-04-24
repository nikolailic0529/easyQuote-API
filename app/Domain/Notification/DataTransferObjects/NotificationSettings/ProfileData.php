<?php

namespace App\Domain\Notification\DataTransferObjects\NotificationSettings;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ProfileData extends Data
{
    public function __construct(
        public readonly FlagsData $isActive = new FlagsData(),
    ) {
    }
}
