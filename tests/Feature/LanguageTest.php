<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * @group build
 */
class LanguageTest extends TestCase
{
    /**
     * Test an ability to view list of available languages.
     */
    public function testCanViewListOfLanguages(): void
    {
        $response = $this->get('api/data/languages')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'name',
                ],
            ]);

        $this->assertNotEmpty($response->json());
    }

    /**
     * Test an ability to view list of contact languages.
     */
    public function testCanViewListOfContactLanguages(): void
    {
        $response = $this->get('api/data/languages/contact')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data'));
    }
}
