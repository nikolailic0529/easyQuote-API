<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;
use App\Http\Middleware\EnforceChangePassword;
use App\Http\Middleware\PerformUserActivity;
use Illuminate\Support\Facades\DB;

/**
 * @group build
 */
class PerformanceTest extends TestCase
{
    use WithFakeUser;

    /**
     * Test Submitted Quotes Querying Performance.
     *
     * @return void
     */
    public function testSubmittedQuotesListingPerformance()
    {        
        $this->authenticate();

        $this->withoutMiddleware([EnforceChangePassword::class]);

        DB::enableQueryLog();

        $this->get('/api/quotes/submitted')->assertOk();

        $queries = DB::getQueryLog();

        $time = collect($queries)->sum('time');

        $this->assertLessThanOrEqual(6, count($queries));

        $this->assertLessThan(15, $time);
    }

    /**
     * Test Drafted Quotes Querying Performance.
     *
     * @return void
     */
    public function testDraftedQuotesListingPerformance()
    {
        $this->authenticate();

        $this->withoutMiddleware([EnforceChangePassword::class]);

        DB::enableQueryLog();

        $this->get('/api/quotes/drafted')->assertOk();

        $queries = DB::getQueryLog();

        $time = collect($queries)->sum('time');

        $this->assertLessThanOrEqual(6, count($queries));

        $this->assertLessThan(15, $time);
    }
}
