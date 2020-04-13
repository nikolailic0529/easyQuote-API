<?php

namespace Tests\Unit;

use App\Models\System\Notification;
use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing
};

class NotificationTest extends TestCase
{
    use WithFakeUser, AssertsListing;

    /**
     * Test Notification listing.
     *
     * @return void
     */
    public function testNotificationListing()
    {
        $response = $this->getJson(url('api/notifications'));

        $this->assertListing($response);

        $query = http_build_query([
            'order_by_created_at' => 'asc',
            'order_by_priority' => 'asc'
        ]);

        $response = $this->getJson(url('api/companies?' . $query));

        $this->assertListing($response);
    }

    /**
     * Test Latest Notifications listing.
     *
     * @return void
     */
    public function testLatestNotificationListing()
    {
        $this->getJson(url('api/notifications/latest'))
            ->assertOk()
            ->assertJsonStructure(['data', 'total']);
    }

    /**
     * Test deleting a new created Notification.
     *
     * @return void
     */
    public function testNotificationDeleting()
    {
        $notification = $this->createFakeNotification();

        $this->deleteJson(url("api/notifications/{$notification->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertSoftDeleted($notification);
    }

    /**
     * Test deleting all the existing Notifications.
     *
     * @return void
     */
    public function testNotificationDeletingAll()
    {
        $this->createFakeNotification();

        $this->deleteJson(url('api/notifications'))
            ->assertOk()
            ->assertExactJson([true]);

        $this->getJson(url('api/notifications/latest'))
            ->assertOk()
            ->assertExactJson([
                'data' => [],
                'total' => 0,
                'read' => 0,
                'unread' => 0
            ]);
    }

    /**
     * Test reading a newly created Notification.
     *
     * @return void
     */
    public function testNotificationReading()
    {
        $notification = $this->createFakeNotification();

        $this->putJson(url("api/notifications/{$notification->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function testNotificationReadingAll()
    {
        $this->createFakeNotification();

        $this->putJson(url('api/notifications'))
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson(url('api/notifications/latest'))->assertOk();

        $notifications = collect($response->json('data'));

        $notifications->each(fn ($n) => $this->assertNotNull($n['read_at']));
    }

    protected function createFakeNotification(): Notification
    {
        return notification()
            ->for($this->user)
            ->message('Test Notification')
            ->url(ui_route('users.profile'))
            ->subject($this->user)
            ->store();
    }
}
