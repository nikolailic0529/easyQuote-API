<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContractTypeTest extends TestCase
{
    /**
     * Test an ability to view contract types.
     *
     * @return void
     */
    public function testCanViewContractTypes()
    {
        $this->authenticateApi();

        $this->getJson('api/contract-types')
            ->assertOk()
            ->assertJsonStructure([
                '*' => ['id', 'type_name', 'type_short_name']
            ]);
    }
}
