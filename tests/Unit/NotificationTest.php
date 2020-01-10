<?php

namespace Tests\Unit;

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
        $response = $this->getJson(url('api/notifications'), $this->authorizationHeader);

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
        $response = $this->getJson(url('api/notifications/latest'), $this->authorizationHeader);

        $response->assertOk()
            ->assertJsonStructure(['data', 'total']);
    }
}
