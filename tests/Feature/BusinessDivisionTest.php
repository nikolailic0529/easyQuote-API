<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BusinessDivisionTest extends TestCase
{
    /**
     * Test an ability to view business divisions.
     *
     * @return void
     */
    public function testCanViewBusinessDivisions()
    {
        $this->authenticateApi();

        $this->getJson('api/business-divisions')
            ->assertOk()
            ->assertJsonStructure([
                '*' => ['id', 'division_name']
            ]);
    }
}
