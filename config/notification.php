<?php

return [
    'settings' => [
        'activities' => [
            'is_active',
            'tasks',
            'appointments',
        ],
        'accounts_and_contacts' => [
            'is_active',
            'ownership_change',
        ],
        'opportunities' => [
            'is_active',
            'ownership_change',
        ],
        'quotes' => [
            'is_active',
            'ownership_change',
            'permissions_change',
            'status_change',
        ],
        'sync' => [
            'is_active',
        ],
        'maintenance' => [
            'is_active',
        ],
        'profile' => [
            'is_active',
        ],
    ],
    'sending_management' => [
        'activities' => [
            'is_active' => [
                \App\Domain\Task\Notifications\TaskExpiredNotification::class,
                \App\Domain\Task\Notifications\TaskDeletedNotification::class,
                \App\Domain\Task\Notifications\TaskCreatedNotification::class,
                \App\Domain\Task\Notifications\InvitedToTaskNotification::class,
                \App\Domain\Task\Notifications\RevokedInvitationFromTaskNotification::class,

                \App\Domain\Appointment\Notifications\AppointmentCreatedNotification::class,
                \App\Domain\Appointment\Notifications\AppointmentDeletedNotification::class,
                \App\Domain\Appointment\Notifications\InvitedToAppointmentNotification::class,
                \App\Domain\Appointment\Notifications\RevokedInvitationFromAppointmentNotification::class,

                \App\Domain\Note\Notifications\NoteCreated::class,
            ],
            'tasks' => [
                \App\Domain\Task\Notifications\TaskExpiredNotification::class,
                \App\Domain\Task\Notifications\TaskDeletedNotification::class,
                \App\Domain\Task\Notifications\TaskCreatedNotification::class,
                \App\Domain\Task\Notifications\InvitedToTaskNotification::class,
                \App\Domain\Task\Notifications\RevokedInvitationFromTaskNotification::class,
            ],
            'appointments' => [
                \App\Domain\Appointment\Notifications\AppointmentCreatedNotification::class,
                \App\Domain\Appointment\Notifications\AppointmentDeletedNotification::class,
                \App\Domain\Appointment\Notifications\InvitedToAppointmentNotification::class,
                \App\Domain\Appointment\Notifications\RevokedInvitationFromAppointmentNotification::class,
            ],
        ],
        'quotes' => [
            'is_active' => [
                \App\Domain\Rescue\Notifications\QuoteExpiresNotification::class,
                \App\Domain\Rescue\Notifications\QuoteDeletedNotification::class,
                \App\Domain\Rescue\Notifications\QuoteSubmittedNotification::class,
                \App\Domain\Rescue\Notifications\QuoteUnravelledNotification::class,
                \App\Domain\Rescue\Notifications\QuoteAccessGrantedNotification::class,
                \App\Domain\Rescue\Notifications\QuoteAccessRevokedNotification::class,

                \App\Domain\Rescue\Notifications\ContractDeletedNotification::class,
                \App\Domain\Rescue\Notifications\ContractSubmittedNotification::class,

                \App\Domain\Worldwide\Notifications\WorldwideQuoteSubmittedNotification::class,
                \App\Domain\Worldwide\Notifications\WorldwideQuoteUnraveledNotification::class,
                \App\Domain\Worldwide\Notifications\WorldwideQuoteOwnershipChangedNotification::class,
            ],
            'status_change' => [
                \App\Domain\Rescue\Notifications\QuoteSubmittedNotification::class,
                \App\Domain\Rescue\Notifications\QuoteUnravelledNotification::class,
                \App\Domain\Rescue\Notifications\ContractSubmittedNotification::class,
                \App\Domain\Worldwide\Notifications\WorldwideQuoteSubmittedNotification::class,
                \App\Domain\Worldwide\Notifications\WorldwideQuoteUnraveledNotification::class,
            ],
            'permissions_change' => [
                \App\Domain\Rescue\Notifications\QuoteAccessGrantedNotification::class,
                \App\Domain\Rescue\Notifications\QuoteAccessRevokedNotification::class,
            ],
            'ownership_change' => [
                \App\Domain\Worldwide\Notifications\WorldwideQuoteOwnershipChangedNotification::class,
            ],
        ],
        'accounts_and_contacts' => [
            'is_active' => [
                \App\Domain\Company\Notifications\CompanyOwnershipChangedNotification::class,
            ],
            'ownership_change' => [
                \App\Domain\Company\Notifications\CompanyOwnershipChangedNotification::class,
            ],
        ],
        'opportunities' => [
            'is_active' => [
                \App\Domain\Worldwide\Notifications\OpportunityOwnershipChangedNotification::class,
            ],
            'ownership_change' => [
                \App\Domain\Worldwide\Notifications\OpportunityOwnershipChangedNotification::class,
            ],
        ],
        'sync' => [
            'is_active' => [
                \App\Domain\Pipeliner\Notifications\SyncStrategyEntitySkippedNotification::class,
                \App\Domain\Pipeliner\Notifications\SyncStrategyModelSkippedNotification::class,
                \App\Domain\Pipeliner\Notifications\ModelSyncFailedNotification::class,
                \App\Domain\Pipeliner\Notifications\ModelSyncCompletedNotification::class,
            ],
        ],
        'maintenance' => [
            'is_active' => [
                \App\Domain\Maintenance\Notifications\MaintenanceScheduledNotification::class,
                \App\Domain\Maintenance\Notifications\MaintenanceFinishedNotification::class,
            ],
        ],
        'profile' => [
            'is_active' => [
                \App\Domain\User\Notifications\PasswordChangedNotification::class,
                \App\Domain\User\Notifications\PasswordExpiringNotification::class,
            ],
        ],
    ],
];
