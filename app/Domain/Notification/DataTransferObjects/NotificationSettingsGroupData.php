<?php

namespace App\Domain\Notification\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class NotificationSettingsGroupData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly string $key,
        #[DataCollectionOf(NotificationSettingsControlData::class)]
        public readonly DataCollection $controls,
    ) {
    }
}
