<?php

namespace Tests\Unit\Traits;

use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;

trait AssertsListing
{
    protected function assertListing(TestResponse $response): void
    {
        $json = $response->json();

        if (Arr::has($json, 'meta')) {
            $this->assertCollectionListing($response);

            return;
        }

        $this->assertGenericListing($response);
    }

    protected function assertGenericListing(TestResponse $response): void
    {
        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'from',
                'to',
                'total',
                'per_page',
                'last_page',
            ]);
    }

    protected function assertCollectionListing(TestResponse $response): void
    {
        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'from',
                    'to',
                    'total',
                    'per_page',
                    'last_page',
                ],
            ]);
    }
}
