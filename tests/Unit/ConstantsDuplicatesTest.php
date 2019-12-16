<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ConstantsDuplicatesTest extends TestCase
{
    /**
     * Test Constant Configuration for Duplicates.
     *
     * @return void
     */
    public function testConstantConfigurationForDuplicates()
    {
        $this->artisan('optimize');

        $duplicates = collect(config('constants'))->duplicates();

        $this->assertEmpty($duplicates);
    }
}
