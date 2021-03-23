<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeUser,
    AssertsListing,
};
use Illuminate\Support\Str;

/**
 * @group build
 */
class ActivityTest extends TestCase
{
    use WithFakeUser, AssertsListing, DatabaseTransactions;

    protected $truncatableTables = ['activity_log'];

    protected function setUp(): void
    {
        parent::setUp();

        activity()->log('test');
    }

    /**
     * Test Activity listing.
     *
     * @return void
     */
    public function testActivityListing()
    {
        $response = $this->postJson(url('api/activities'));

        $this->assertListing($response);

        $response = $this->postJson(url('api/activities'), [
            'order_by_created' => 'asc',
            'search' => Str::random(10)
        ]);

        $this->assertListing($response);
    }

    /**
     * Test Activity listing by specified types.
     *
     * @return void
     */
    public function testActivityListingByTypes()
    {
        $types = config('activitylog.types');

        collect($types)->each(function ($type) {
            $response = $this->postJson(url('api/activities'), ['types' => [$type]]);
            $this->assertListing($response);
        });

        $response = $this->postJson(url('api/activities'), compact('types'));

        $this->assertListing($response);
    }

    /**
     * Test Activity listing by specified subject types.
     *
     * @return void
     */
    public function testActivityListingBySubjectTypes()
    {
        $subject_types = array_keys(config('activitylog.subject_types'));

        collect($subject_types)->each(function ($type) {
            $response = $this->postJson(url('api/activities'), ['subject_types' => [$type]]);
            $this->assertListing($response);
        });

        $response = $this->postJson(url('api/activities'), compact('subject_types'));
        $this->assertListing($response);
    }

    /**
     * Test Activity listing by specified periods.
     *
     * @return void
     */
    public function testActivityListingByPeriods()
    {
        collect(config('activitylog.periods'))->each(function ($period) {
            $response = $this->postJson(url('api/activities'), compact('period'));
            $this->assertListing($response);
        });
    }

    /**
     * Test Activity Export as PDF.
     *
     * @return void
     */
    public function testActivityExportPdf()
    {
        $this->post(url('api/activities/export/pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test Activity Export as CSV.
     *
     * @return void
     */
    public function testActivityExportCsv()
    {
        $this->post(url('api/activities/export/csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/plain');
    }
}
