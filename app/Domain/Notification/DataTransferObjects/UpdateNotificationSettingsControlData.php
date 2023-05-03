<?php

namespace App\Domain\Notification\DataTransferObjects;

use Spatie\LaravelData\Data;

final class UpdateNotificationSettingsControlData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly bool $email_notif,
        public readonly bool $app_notif,
    ) {
    }
}
