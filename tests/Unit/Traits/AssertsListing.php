<?php

namespace Tests\Unit\Traits;

use Illuminate\Foundation\Testing\TestResponse;

trait AssertsListing
{
    public function assertListing(TestResponse $response)
    {
        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'from',
                'to',
                'total',
                'per_page',
                'last_page'
            ]);
    }
}
