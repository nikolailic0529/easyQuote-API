<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceChangePassword;
use App\Http\Middleware\PerformUserActivity;
use App\Models\User;
use Tests\TestCase;
use DB;

class QuotePerformanceTest extends TestCase
{
    /**
     * Test Submitted Quotes Querying Performance.
     *
     * @return void
     */
    public function testSubmittedQuotesListingPerformance()
    {
        $this->be(User::first(), 'api');

        $this->withoutMiddleware([PerformUserActivity::class, EnforceChangePassword::class]);

        DB::enableQueryLog();

        $this->get('/api/quotes/submitted')->assertOk();
        $this->get('/api/quotes/submitted')->assertOk();

        $queries = DB::getQueryLog();

        $time = collect($queries)->sum('time');

        $this->assertLessThanOrEqual(16, count($queries));

        $this->assertLessThan(23, $time);
    }

    /**
     * Test Drafted Quotes Querying Performance.
     *
     * @return void
     */
    public function testDraftedQuotesListingPerformance()
    {
        $this->be(User::first(), 'api');

        $this->withoutMiddleware([PerformUserActivity::class, EnforceChangePassword::class]);

        DB::enableQueryLog();

        $this->get('/api/quotes/drafted')->assertOk();
        $this->get('/api/quotes/drafted')->assertOk();

        $queries = DB::getQueryLog();

        $time = collect($queries)->sum('time');

        $this->assertLessThanOrEqual(18, count($queries));

        $this->assertLessThan(23, $time);
    }
}
