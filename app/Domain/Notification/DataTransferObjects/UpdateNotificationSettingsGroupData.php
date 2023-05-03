<?php

namespace App\Domain\Notification\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class UpdateNotificationSettingsGroupData extends Data
{
    public function __construct(
        public readonly string $key,
        #[DataCollectionOf(UpdateNotificationSettingsControlData::class)]
        public readonly DataCollection $controls,
    ) {
    }
}
