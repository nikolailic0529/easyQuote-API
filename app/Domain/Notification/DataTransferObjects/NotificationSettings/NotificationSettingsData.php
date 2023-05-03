<?php

namespace App\Domain\Notification\DataTransferObjects\NotificationSettings;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class NotificationSettingsData extends Data
{
    public function __construct(
        public readonly ActivitiesData $activities = new ActivitiesData(),
        public readonly AccountsAndContactsData $accountsAndContacts = new AccountsAndContactsData(),
        public readonly OpportunitiesData $opportunities = new OpportunitiesData(),
        public readonly QuotesData $quotes = new QuotesData(),
        public readonly SyncData $sync = new SyncData(),
        public readonly MaintenanceData $maintenance = new MaintenanceData(),
        public readonly ProfileData $profile = new ProfileData(),
    ) {
    }
}
