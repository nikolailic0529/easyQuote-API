<?php

namespace Tests\Unit;

use App\Domain\Notification\Listeners\EnsureNotificationCanBeSent;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Models\TestNotification;

/**
 * @group build
 */
class NotificationManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function testNotificationIsBeingSentToMailChannelWhenMailChannelEnabled(): void
    {
        $notification = new TestNotification();

        /** @var User $notifiable */
        $notifiable = User::factory()->create();
        $notifiable->notification_settings->activities->isActive->emailNotif = true;
        $notifiable->notification_settings->activities->isActive->appNotif = false;

        $event = new NotificationSending($notifiable, $notification, 'mail');

        /** @var EnsureNotificationCanBeSent $listener */
        $listener = $this->app->makeWith(EnsureNotificationCanBeSent::class, [
            'sending_management' => [
                'activities' => [
                    'is_active' => [
                        $notification::class,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($listener->handle($event));
    }

    public function testNotificationIsBeingSentToDatabaseChannelWhenDatabaseChannelEnabled(): void
    {
        $notification = new TestNotification();

        /** @var User $notifiable */
        $notifiable = User::factory()->create();
        $notifiable->notification_settings->activities->isActive->emailNotif = false;
        $notifiable->notification_settings->activities->isActive->appNotif = true;

        $event = new NotificationSending($notifiable, $notification, 'database.custom');

        /** @var EnsureNotificationCanBeSent $listener */
        $listener = $this->app->makeWith(EnsureNotificationCanBeSent::class, [
            'config' => [
                'sending_management' => [
                    'activities' => [
                        'is_active' => [
                            $notification::class,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($listener->handle($event));
    }

    public function testNotificationIsNotBeingSentToMailChannelWhenMailChannelDisabled(): void
    {
        $notification = new TestNotification();

        /** @var User $notifiable */
        $notifiable = User::factory()->create();
        $notifiable->notification_settings->activities->isActive->emailNotif = false;
        $notifiable->notification_settings->activities->isActive->appNotif = true;

        $event = new NotificationSending($notifiable, $notification, 'mail');

        /** @var EnsureNotificationCanBeSent $listener */
        $listener = $this->app->makeWith(EnsureNotificationCanBeSent::class, [
            'config' => [
                'sending_management' => [
                    'activities' => [
                        'is_active' => [
                            $notification::class,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertFalse($listener->handle($event));
    }

    public function testNotificationIsNotBeingSentToDatabaseChannelWhenDatabaseChannelDisabled(): void
    {
        $notification = new TestNotification();

        /** @var User $notifiable */
        $notifiable = User::factory()->create();
        $notifiable->notification_settings->activities->isActive->emailNotif = true;
        $notifiable->notification_settings->activities->isActive->appNotif = false;

        $event = new NotificationSending($notifiable, $notification, 'database.custom');

        /** @var EnsureNotificationCanBeSent $listener */
        $listener = $this->app->makeWith(EnsureNotificationCanBeSent::class, [
            'config' => [
                'sending_management' => [
                    'activities' => [
                        'is_active' => [
                            $notification::class,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertFalse($listener->handle($event));
    }

    public function testNotificationIsBeingSentToUndefinedChannel(): void
    {
        $notification = new TestNotification();

        /** @var User $notifiable */
        $notifiable = User::factory()->create();
        $notifiable->notification_settings->activities->isActive->emailNotif = false;
        $notifiable->notification_settings->activities->isActive->appNotif = false;

        $event = new NotificationSending($notifiable, $notification, Str::random());

        /** @var EnsureNotificationCanBeSent $listener */
        $listener = $this->app->makeWith(EnsureNotificationCanBeSent::class, [
            'config' => [
                'sending_management' => [
                    'activities' => [
                        'is_active' => [
                            $notification::class,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($listener->handle($event));
    }
}
