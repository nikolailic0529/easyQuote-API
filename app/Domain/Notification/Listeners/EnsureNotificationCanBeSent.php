<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\User\Models\User;
use Illuminate\Notifications\Events\NotificationSending;

final class EnsureNotificationCanBeSent
{
    public function __construct(
        public readonly array $config,
    ) {
    }

    public function handle(NotificationSending $event): bool
    {
        // Don't care if notifiable is not a user.
        if (!$event->notifiable instanceof User) {
            return true;
        }

        // Don't send notifications to the inactive users.
        if (!$event->notifiable->isActive()) {
            return false;
        }

        // Ensure the notification can be sent per the user settings.
        if (!$this->shouldSendNotificationPerSettings($event)) {
            return false;
        }

        return true;
    }

    private function shouldSendNotificationPerSettings(NotificationSending $event): bool
    {
        // Don't care about the channels which are undefined in the settings.
        if (!($channel = $this->channelToProperty($event->channel))) {
            return true;
        }

        $notificationSettings = $event->notifiable->notification_settings->toArray();

        foreach ($this->config['sending_management'] as $group => $subGroups) {
            if (!isset($notificationSettings[$group])) {
                continue;
            }

            foreach ($subGroups as $subGroup => $manageable) {
                if (!isset($notificationSettings[$group][$subGroup])) {
                    continue;
                }

                if (!isset($notificationSettings[$group][$subGroup][$channel])) {
                    continue;
                }

                /**
                 * @var list<class-string> $manageable
                 */
                foreach ($manageable as $class) {
                    if ($event->notification instanceof $class && !$notificationSettings[$group][$subGroup][$channel]) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function channelToProperty(string $channel): ?string
    {
        return match ($channel) {
            'mail' => 'email_notif',
            'database.custom' => 'app_notif',
            default => null,
        };
    }
}
