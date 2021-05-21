<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaceTest extends TestCase
{
    /**
     * Test an ability to view a list of existing space entities.
     *
     * @return void
     */
    public function testCanViewListOfExistingSpaces()
    {
        $this->authenticateApi();

        $this->getJson('api/spaces')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'space_name'
                ]
            ]);

    }
}
