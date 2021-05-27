<?php

namespace Tests\Feature;

use App\Models\Space;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SpaceTest extends TestCase
{
    use DatabaseTransactions;

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

    /**
     * Test an ability to batch put spaces.
     *
     * @return void
     */
    public function testCanBatchPutSpaces()
    {
        $this->authenticateApi();

        /** @var Space $existingSpace */
        $existingSpace = factory(Space::class)->create();

        $newSpaces = factory(Space::class, 2)->raw([
            'id' => null,
        ]);

        $spacesData = array_merge($newSpaces, [[
            'id' => $existingSpace->getKey(),
            'space_name' => $existingSpace->space_name,
        ]]);

        $this->putJson('api/spaces', [
            'spaces' => $spacesData
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'space_name'
                ]
            ]);

    }
}
