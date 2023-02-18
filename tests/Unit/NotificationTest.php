<?php

namespace Tests\Unit;

use App\Domain\Notification\Models\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\{AssertsListing};

/**
 * @group build
 */
class NotificationTest extends TestCase
{
    use AssertsListing;
    use DatabaseTransactions;

    /**
     * Test Notification listing.
     *
     * @return void
     */
    public function testNotificationListing()
    {
        $this->authenticateApi();

        $response = $this->getJson(url('api/notifications'));

        $this->assertListing($response);

        $query = http_build_query([
            'order_by_created_at' => 'asc',
            'order_by_priority' => 'asc',
        ]);

        $response = $this->getJson(url('api/companies?'.$query));

        $this->assertListing($response);
    }

    /**
     * Test Latest Notifications listing.
     *
     * @return void
     */
    public function testLatestNotificationListing()
    {
        $this->authenticateApi();

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
        $this->authenticateApi();

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
        $this->authenticateApi();

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
                'unread' => 0,
            ]);
    }

    /**
     * Test reading a newly created Notification.
     *
     * @return void
     */
    public function testNotificationReading()
    {
        $this->authenticateApi();

        $notification = $this->createFakeNotification();

        $this->putJson(url("api/notifications/{$notification->id}"))
            ->assertOk()
            ->assertExactJson([true]);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function testNotificationReadingAll()
    {
        $this->authenticateApi();

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
            ->for($this->app['auth']->user())
            ->message('Test Notification')
            ->url(ui_route('users.profile'))
            ->subject($this->app['auth']->user())
            ->push();
    }
}
