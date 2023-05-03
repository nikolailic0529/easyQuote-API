<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * @group build
 */
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
                '*' => ['id', 'type_name', 'type_short_name'],
            ]);
    }
}
