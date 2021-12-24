<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group build
 */
class LanguageTest extends TestCase
{
    /**
     * Test an ability to view list of available languages.
     *
     * @return void
     */
    public function testCanViewListOfLanguages()
    {
        $response = $this->get('api/data/languages')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'name'
                ]
            ]);

        $this->assertNotEmpty($response->json());
    }
}
