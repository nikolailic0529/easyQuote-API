<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * @group build
 */
class TimezoneTest extends TestCase
{
    /**
     * Test an ability to view a list of available timezones.
     *
     * @return void
     */
    public function testCanViewListOfTimezones()
    {
        $response = $this->get('api/data/timezones')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'text', 'value',
                ],
            ]);

        $this->assertNotEmpty($response->json());
    }
}
