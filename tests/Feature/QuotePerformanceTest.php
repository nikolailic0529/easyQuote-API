<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceChangePassword;
use App\Models\Quote\Quote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;

class QuotePerformanceTest extends TestCase
{
    use WithFakeUser, DatabaseTransactions;

    /**
     * Test Submitted Quotes Querying Performance.
     *
     * @return void
     */
    public function testSubmittedQuotesListingPerformance()
    {
        $this->markTestSkipped();

        factory(Quote::class, 100)->create();

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
        $this->markTestSkipped();

        factory(Quote::class, 100)->create();

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
