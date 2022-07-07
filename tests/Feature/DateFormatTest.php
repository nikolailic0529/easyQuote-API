<?php

namespace Tests\Feature;

use Tests\TestCase;

class DateFormatTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanViewListOfDateFormats(): void
    {
        $response = $this->getJson('/api/data/dateformats')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'value',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
        $this->assertContains('Auto', $response->json('data.*.name'));
    }
}
