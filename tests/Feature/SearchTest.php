<?php

namespace Tests\Feature;

use Tests\TestCase;

class SearchTest extends TestCase
{
    /**
     * Test an ability to queue search rebuild.
     */
    public function testCanQueueSearchRebuild(): void
    {
        $this->authenticateApi();

        $this->patchJson('api/search/queue-rebuild')
//            ->dump()
            ->assertNoContent();
    }
}
