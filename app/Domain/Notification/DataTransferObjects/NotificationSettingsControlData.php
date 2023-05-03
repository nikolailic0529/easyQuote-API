<?php

namespace App\Domain\Notification\DataTransferObjects;

use Spatie\LaravelData\Data;

final class NotificationSettingsControlData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly string $key,
        public readonly bool $email_notif,
        public readonly bool $app_notif,
    ) {
    }
}
