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
        $response = $this->getJson(url('api/notifications/latest'));

        $response->assertOk()
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

        $response = $this->deleteJson(url("api/notifications/{$notification->id}"));

        $response->assertOk()
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

        $response = $this->deleteJson(url('api/notifications'));

        $response->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson(url('api/notifications/latest'));

        $response->assertOk()
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

        $response = $this->putJson(url("api/notifications/{$notification->id}"));

        $response->assertOk()
            ->assertExactJson([true]);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function testNotificationReadingAll()
    {
        $this->createFakeNotification();

        $response = $this->putJson(url('api/notifications'));

        $response->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson(url('api/notifications/latest'));

        $response->assertOk();

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
